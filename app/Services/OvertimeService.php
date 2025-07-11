<?php

namespace App\Services;

use App\Enums\DailyAttendance;
use App\Enums\EventType;
use App\Enums\FormulaAmountType;
use App\Enums\FormulaComponentEnum;
use App\Enums\RateType;
use App\Models\Event;
use App\Models\Formula;
use App\Models\Overtime;
use App\Models\OvertimeRequest;
use App\Models\PayrollComponent;
use App\Models\TaskHour;
use App\Models\TaskRequest;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Collection;

class OvertimeService
{
    public static function calculateOb(User $user, Collection $overtimeRequests): int|float
    {
        $umk = $user->branch?->umk ?? 0;
        $basicSalary = $user->payrollInfo?->basic_salary > $umk ? $user->payrollInfo?->basic_salary : $umk;
        $totalDurationInHours = 0;
        foreach ($overtimeRequests as $overtimeRequest) {
            $realDuration = Carbon::parse($overtimeRequest->real_duration);
            $durationInHours = $realDuration->hour + ($realDuration->minute / 60) + ($realDuration->second / 3600);

            $totalDurationInHours += ($durationInHours > 9 ? 9 : $durationInHours);
        }

        return ($basicSalary / 160) * $totalDurationInHours;
    }

    public static function calculateObSunEnglish(User $user, Overtime $overtime, Collection $overtimeRequests): int|float
    {
        $totalDurationInHours = 0;
        foreach ($overtimeRequests as $overtimeRequest) {
            $realDuration = Carbon::parse($overtimeRequest->real_duration);
            $durationInHours = $realDuration->hour + ($realDuration->minute / 60) + ($realDuration->second / 3600);

            $totalDurationInHours += ($durationInHours > 9 ? 9 : $durationInHours);
        }

        $rate = $overtime->rate_type->is(RateType::AMOUNT) && !is_null($overtime->rate_amount) ? floatval($overtime->rate_amount) : 12500;

        return $rate * $totalDurationInHours;
    }

    public static function calculate(User $user, ?string $startPeriod = null, ?string $endPeriod = null, float $basicSalary): int|float
    {
        if (!is_null($startPeriod)) $startPeriod = date('Y-m-d', strtotime($startPeriod));
        if (!is_null($endPeriod)) $endPeriod = date('Y-m-d', strtotime($endPeriod));

        if ($user->overtimes->contains(fn($ov) => strtolower($ov->name) == 'ob')) {
            $overtimeRequests = $user->overtimeRequests()->where('overtime_id', $user->overtimes->whereIn('name', ['ob', 'OB'])->value('id'))->whereDateBetween($startPeriod, $endPeriod)->approved()->get();
            return self::calculateOb($user, $overtimeRequests);
        }

        if ($user->overtimes->contains(fn($ov) => strtolower($ov->name) == 'OB_SUN_ENGLISH')) {
            $overtime = $user->overtimes->where('name', 'OB_SUN_ENGLISH')->first();
            if ($overtime) {
                $overtimeRequests = $user->overtimeRequests()->where('overtime_id', $overtime->id)->whereDateBetween($startPeriod, $endPeriod)->approved()->get();
                return self::calculateObSunEnglish($user, $overtime, $overtimeRequests);
            } else {
                return 0;
            }
        }

        $overtimeRequestGroups = $user->overtimeRequests()->whereIn('overtime_id', $user->overtimes->pluck('id'))->whereDateBetween($startPeriod, $endPeriod)->approved()->get()->groupBy(['overtime_id', fn($data) => $data->date]);

        if ($overtimeRequestGroups->count() <= 0) return 0;

        $userOvertimes = $user->overtimes->load([
            'formulas.formulaComponents',
            'overtimeMultipliers' => fn($q) => $q->select('overtime_id', 'is_weekday', 'start_hour', 'end_hour', 'multiply'),
            'overtimeRoundings' => fn($q) => $q->select('overtime_id', 'start_minute', 'end_minute', 'rounded')
        ]);

        $amount = 0;
        foreach ($overtimeRequestGroups as $overtimeId => $overtimeRequestGroupDates) {
            foreach ($overtimeRequestGroupDates as $overtimeDate => $overtimeRequests) {

                $overtime = $userOvertimes->where('id', $overtimeId)->first();
                if (!$overtime) continue;

                $overtimeDate = Carbon::parse($overtimeDate);

                // set overtime duration to minutes. 02:00:00 become 120
                // $overtimeDuration = Carbon::parse($overtimeRequest->real_duration)->diffInMinutes(Carbon::parse('00:00:00'), true);
                $overtimeDuration = $overtimeRequests->sum(fn($d) => Carbon::parse($d->real_duration)->diffInMinutes(Carbon::parse('00:00:00'), true));

                if ($overtimeRounding = $overtime->overtimeRoundings->where('start_minute', '<=', $overtimeDuration)->where('end_minute', '>=', $overtimeDuration)->first()) {
                    $overtimeDuration = $overtimeRounding->rounded;
                }

                // set overtime duration to hour. 120 become 2
                $overtimeDuration = round($overtimeDuration / 60);

                // dd($overtime->overtimeMultipliers?->toArray());
                $overtimeMultipliers = collect([
                    [
                        'duration' => 1,
                        'multiply' => 1,
                    ]
                ]);

                if ($overtime->overtimeMultipliers->count()) {
                    if (config('app.name') == 'LUMORA') {
                        $nationalHolidays = Event::select('id', 'company_id', 'start_at', 'end_at')
                            ->whereNationalHoliday()
                            ->where('company_id', $user->company_id)
                            ->whereDateBetween($startPeriod, $endPeriod)
                            ->get();

                        $inNationalHoliday = $nationalHolidays->contains(function ($nh) use ($overtimeDate) {
                            $start = Carbon::parse($nh->start_at)->startOfDay();
                            $end = Carbon::parse($nh->end_at)->endOfDay();
                            return $overtimeDate->between($start, $end);
                        });

                        if (!$inNationalHoliday) {
                            $schedule = ScheduleService::getTodaySchedule(user: $user, datetime: $overtimeDate, scheduleColumn: ['id'], shiftColumn: ['id', 'is_dayoff']);
                        }

                        if ($inNationalHoliday) {
                            $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', false)->sortBy('start_hour');
                        } elseif (!$inNationalHoliday && $schedule?->shift?->is_dayoff) {
                            $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', false)->sortBy('start_hour');
                        } else {
                            $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', true)->sortBy('start_hour');
                        }
                    } else {
                        if ($overtimeDate->isWeekday()) {
                            $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', true)->sortBy('start_hour');
                        } else {
                            $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', false)->sortBy('start_hour');
                        }
                    }

                    $overtimeMultipliers = self::calculateOvertimeBreakdown($overtimeDuration, $overtimeMultipliers);
                }

                $overtimeAmountMultiply = 0;
                // if overtime paid per day. else paid per hour
                if (!is_null($overtime->compensation_rate_per_day) && $overtime->compensation_rate_per_day > 0) {
                    $overtimeAmountMultiply = $overtime->compensation_rate_per_day;
                    // $amount += $multiply * $overtimeAmountMultiply;
                } else {
                    switch ($overtime->rate_type) {
                        case RateType::AMOUNT:
                            $overtimeAmountMultiply = $overtime->rate_amount;

                            break;
                        case RateType::BASIC_SALARY:
                            $overtimeAmountMultiply = $basicSalary / $overtime->rate_amount;
                            break;
                        // case RateType::ALLOWANCES:
                        //     $overtimeAmountMultiply = 0;

                        //     foreach ($overtime->overtimeAllowances as $overtimeAllowance) {
                        //         $overtimeAmountMultiply += $overtimeAllowance->payrollComponent?->amount > 0 ? ($overtimeAllowance->payrollComponent?->amount / $overtimeAllowance->amount) : 0;
                        //     }

                        //     break;
                        // case RateType::FORMULA:
                        //     // dump('OKEE');
                        //     // $overtimeAmountMultiply = FormulaService::calculate(user: $user, model: $overtime, formulas: $overtime->formulas, startPeriod: $cutOffStartDate, endPeriod: $cutOffEndDate);


                        //     $overtimeAmountMultiply = self::calculateFormula($user, $overtimeRequest, $overtime, $overtime->formulas, $startPeriod, $endPeriod);
                        //     break;
                        default:
                            $overtimeAmountMultiply = 0;

                            break;
                    }
                    // $amount += ($overtimeDuration * $multiply) * $overtimeAmountMultiply;
                }

                if ($overtimeMultipliers->count()) {
                    $overtimeAmountMultiply = $overtimeMultipliers->sum(function ($om) use ($overtimeAmountMultiply) {
                        return ($om['duration'] * $om['multiply']) * $overtimeAmountMultiply;
                    });
                }

                $amount += $overtimeAmountMultiply;
            }
        }

        return $amount;
    }

    public static function calculateOvertimeBreakdown(float $totalOvertimeHours, Collection $rules)
    {
        return $rules
            ->reduce(function ($carry, $rule, $key) use (&$totalOvertimeHours) {
                if ($totalOvertimeHours <= 0) {
                    return $carry;
                }

                $rangeDuration = $rule['end_hour'] - $rule['start_hour'] + 1;
                $applicableDuration = min($totalOvertimeHours, $rangeDuration);

                if ($rangeDuration > 0 && $applicableDuration > 0) {
                    $carry[$key] = [
                        'duration' => $applicableDuration,
                        'multiply' => $rule['multiply'],
                    ];

                    $totalOvertimeHours -= $applicableDuration;
                }

                return $carry;
            }, collect());
    }

    public static function calculateFormula(User $user, OvertimeRequest $overtimeRequest, Overtime $model, Collection $formulas, string|DateTime $startPeriod = null, string|DateTime $endPeriod = null): int|float
    {
        $amount = 0;
        // looping semua formula, apabila kondisinya cocok langsung return
        foreach ($formulas as $formula) {
            // dump($formula->toArray());
            switch ($formula->component) {
                case FormulaComponentEnum::DAILY_ATTENDANCE:
                    foreach ($formula->formulaComponents as $formulaComponent) {
                        switch ($formulaComponent->value) {
                            case DailyAttendance::PRESENT->value:
                                // $presentAttendance = AttendanceService::getTotalPresent($user, $startPeriod, $endPeriod);
                                $amount = self::sumAmount($model, $formula, $startPeriod, $endPeriod, $user);
                                return $amount;
                                break;
                            case DailyAttendance::ALPA->value:
                                // $alphaAttendance = AttendanceService::getTotalAlpa($user, $startPeriod, $endPeriod);
                                $amount = self::sumAmount($model, $formula, $startPeriod, $endPeriod, $user);
                                return $amount;
                                break;
                            default:
                                //

                                break;
                        }
                    }
                    return $amount;
                case FormulaComponentEnum::SHIFT:
                    // cek apakah overtimeRequest pernah dilakukan di shift ini
                    $isTrue = self::isOvertimeInShift($overtimeRequest, $formula->formulaComponents->pluck('value')?->toArray() ?? [], $startPeriod, $endPeriod);
                    if ($isTrue) {
                        $amount = self::sumAmount($model, $formula, $startPeriod, $endPeriod, $user);
                        return $amount;
                    }
                    break;
                case FormulaComponentEnum::BRANCH:
                    dump('BRANCH');
                    break;
                case FormulaComponentEnum::EMPLOYEMENT_STATUS:
                    dump('BRANCH');
                    break;
                case FormulaComponentEnum::JOB_POSITION:
                    dump('BRANCH');
                    break;
                case FormulaComponentEnum::GENDER:
                    dump('BRANCH');
                    break;
                case FormulaComponentEnum::RELIGION:
                    dump('BRANCH');
                    break;
                case FormulaComponentEnum::MARITAL_STATUS:
                    dump('BRANCH');
                    break;
                case FormulaComponentEnum::ELSE:
                    $amount = self::sumAmount($model, $formula, $startPeriod, $endPeriod, $user);
                    return $amount;
                    break;

                default:
                    return $amount;
            }
        }
    }

    public static function sumAmount(PayrollComponent|Overtime $model, Formula $formula, string|DateTime $startPeriod, string|DateTime $endPeriod, ?User $user = null): int|float
    {
        $amount = 0;
        switch ($formula->amount_type) {
            case FormulaAmountType::SALARY_PER_SCHEDULE_CALENDAR_DAY:
                $totalWorkingDays = ScheduleService::getTotalWorkingDaysInPeriod($user, $startPeriod, $endPeriod);
                if ($totalWorkingDays > 0) {
                    $amount = ($user?->payrollInfo?->basic_salary ?? 0) / $totalWorkingDays;
                } else {
                    $amount = 0;
                }
                break;
            case FormulaAmountType::FULL_SALARY:
                $amount = $user?->payrollInfo?->basic_salary ?? 0;
                break;
            case FormulaAmountType::HALF_OF_SALARY:
                $amount = ($user?->payrollInfo?->basic_salary ?? 0) / 2;
                break;
            default:
                $amount = $formula->amount ?? 0;
                break;
        }

        // if ($model instanceof PayrollComponent && $model->type->is(PayrollComponentType::DEDUCTION)) {
        //     $amount = -abs($amount);
        // }

        return $amount;
    }

    /**
     * sync formula with related model
     *
     * @param  Formula                      $formula
     * @param  \BackedEnum|string|int|float $value
     */
    public static function matchComponentValue(Formula $formula, \BackedEnum|string|int|float $value = null): bool
    {
        if ($value instanceof \BackedEnum) $value = $value->value;

        $formulaComponent = $formula->formulaComponents->where('value', $value)->first();

        return !is_null($formulaComponent);
    }

    public static function isOvertimeInShift(OvertimeRequest $overtimeRequest, array $shiftIds, string $startPeriod, string $endPeriod)
    {
        $startPeriod = Carbon::parse($startPeriod);
        $endPeriod = Carbon::parse($endPeriod);
        $overtimeRequestDate = Carbon::parse($overtimeRequest->date);

        $shifts = collect($shiftIds);
        $shiftIds = $shifts->filter(fn($value) => is_numeric($value))->values()->toArray();
        $nationalHoliday = $shifts->filter(fn($value) => $value == 'national_holiday')->values()?->toArray()[0] ?? null;
        $companyHoliday = $shifts->filter(fn($value) => $value == 'company_holiday')->values()?->toArray()[0] ?? null;

        if (in_array($overtimeRequest->shift_id, $shiftIds) && $overtimeRequestDate->between($startPeriod, $endPeriod)) {
            return true;
        }

        if ($nationalHoliday) {
            $nationalHolidayDates = EventService::getDates(EventType::NATIONAL_HOLIDAY, $startPeriod, $endPeriod);
            if (in_array($overtimeRequest->date, $nationalHolidayDates)) return true;
        }

        if ($companyHoliday) {
            $companyHolidayDates = EventService::getDates(EventType::COMPANY_HOLIDAY, $startPeriod, $endPeriod);
            if (in_array($overtimeRequest->date, $companyHolidayDates)) return true;
        }

        return false;
    }


    public static function calculateTaskOvertime(User $user, string $startPeriod = null, string $endPeriod = null)
    {
        if (!is_null($startPeriod)) $startPeriod = date('Y-m-d', strtotime($startPeriod));
        if (!is_null($endPeriod)) $endPeriod = date('Y-m-d', strtotime($endPeriod));

        $tasks = $user->tasks()
            ->select('id')
            ->select('id', 'min_working_hour', 'working_period', 'weekday_overtime_rate', 'weekend_overtime_rate')
            ->withPivot('task_hour_id')
            ->get();

        $taskHours = TaskHour::select('id', 'task_id', 'min_working_hour', 'max_working_hour')->whereIn('id', $tasks->pluck('pivot.task_hour_id'))->get();
        if ($taskHours->count() <= 0) return 0;

        $amount = 0;
        foreach ($taskHours as $taskHour) {
            $task = $tasks->where('id', $taskHour->task_id)->first();
            $overtimeRequests = TaskRequest::select('id', 'start_at', 'end_at')->where('user_id', $user->id)->where('task_hour_id', $taskHour->id)->whereDateBetween($startPeriod, $endPeriod)->approved()->orderBy('start_at')->get();

            if (!$task || $overtimeRequests->count() <= 0) continue;

            $totalDurationInHours = 0;
            $sisa = 0;
            $lastOvertimeRequest = null;
            foreach ($overtimeRequests as $overtimeRequest) {
                $start = Carbon::parse($overtimeRequest->start_at);
                $end = Carbon::parse($overtimeRequest->end_at);
                $totalDurationInHours += $end->diffInHours($start); // Hitung durasi dalam jam
                if ($totalDurationInHours > $taskHour->max_working_hour) {
                    $lastOvertimeRequest = $overtimeRequest;
                    $sisa = $totalDurationInHours - $taskHour->max_working_hour;
                    break;
                }
            }

            if ($totalDurationInHours <= $taskHour->max_working_hour) continue;

            $totalHourWeekday = 0;
            $totalHourWeekend = 0;
            $newOvertimeRequests = $overtimeRequests->skipUntil(fn($d) => $d->id === $lastOvertimeRequest->id);
            foreach ($newOvertimeRequests as $newOvertimeRequest) {
                $overtimeDate = Carbon::parse($newOvertimeRequest->start_at);
                $start = Carbon::parse($newOvertimeRequest->start_at);
                $end = Carbon::parse($newOvertimeRequest->end_at);
                if ($overtimeDate->isWeekday()) {
                    $totalHourWeekday += ($sisa > 0 ? $sisa : $end->diffInHours($start));
                } else {
                    $totalHourWeekend += ($sisa > 0 ? $sisa : $end->diffInHours($start));
                }
                $sisa = 0;
            }

            $amount += ($totalHourWeekday * $task->weekday_overtime_rate) + ($totalHourWeekend * $task->weekend_overtime_rate);
        }

        return $amount;
    }
}
