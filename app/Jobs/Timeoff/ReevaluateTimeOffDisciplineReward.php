<?php

namespace App\Jobs\Timeoff;

use App\Enums\TimeoffPolicyType;
use App\Enums\UserType;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Event;
use App\Models\TimeoffPolicy;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReevaluateTimeOffDisciplineReward implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $today;
    public Carbon $startDate;
    public Carbon $endDate;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $endDate = null, ?string $startDate = null)
    {
        // $this->endDate = $endDate
        //     ? Carbon::createFromFormat('Y-m-d', $endDate)
        //     : Carbon::now()->subMonth()->endOfMonth();

        // $this->startDate = $startDate
        //     ? Carbon::createFromFormat('Y-m-d', $startDate)
        //     : $this->endDate->copy()->subMonths(4)->startOfMonth();

        $this->today = date('Y-m-01');
        $this->endDate = $endDate
            ? Carbon::createFromFormat('Y-m-d', $endDate)
            : Carbon::now()->subMonth()->endOfMonth();

        $this->startDate = $startDate
            ? Carbon::createFromFormat('Y-m-d', $startDate)
            : Carbon::now()->subMonths(4)->startOfMonth();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (config('app.name') != 'SUNSHINE') return;

        // $fourMonthsAgo = Carbon::createFromDate(2025, 1, 1);
        // $today = Carbon::createFromDate(2025, 4, 30);

        $fourMonthsAgo = $this->startDate;
        $startDate = $this->endDate;

        dump($this->today);
        dump($fourMonthsAgo->format('Y-m-d'));
        dump($startDate->format('Y-m-d'));

        $effectiveEndDate = $startDate->month == 11 || $startDate->month == 12 ? $startDate->copy()->addYear()->endOfYear()->format('Y-m-d') : date('Y-12-31');
        $description = sprintf("AUTOMATICALLY GENERATED FROM THE SYSTEM (EO %s - %s)", $fourMonthsAgo->format('Y-m-d'), $startDate->format('Y-m-d'));

        $dateRange = CarbonPeriod::create($fourMonthsAgo, $startDate);

        $companies = Company::select('id')->where('id', 1)->get();

        foreach ($companies as $company) {
            $timeoffPolicyId = TimeoffPolicy::select('id')->where('type', TimeoffPolicyType::EXTRA_OFF)->firstOrFail()->id;
            $companyHolidays = Event::selectMinimalist()->where('company_id', $company->id)->whereDateBetween($fourMonthsAgo, $startDate)->whereCompanyHoliday()->get();
            $nationalHolidays = Event::selectMinimalist()->where('company_id', $company->id)->whereDateBetween($fourMonthsAgo, $startDate)->whereNationalHoliday()->get();

            $timeoffPolicyIds = TimeoffPolicy::where('company_id', $company->id)
                ->whereIn('type', [
                    TimeoffPolicyType::SICK_WITH_CERTIFICATE,
                    TimeoffPolicyType::SICK_WITHOUT_CERTIFICATE,
                    TimeoffPolicyType::UNPAID_LEAVE,
                    TimeoffPolicyType::MATERNITY_LEAVE,
                ])->get(['id']);

            $availableUsers = User::where('company_id', $company->id)
                // ->where('id', 128)
                ->whereIn('type', [UserType::ADMINISTRATOR, UserType::USER])
                ->whereDoesntHave('timeoffs', function ($q) use ($fourMonthsAgo, $startDate, $timeoffPolicyIds) {
                    $q->approved()
                        ->whereDate('start_at', '>=', $fourMonthsAgo->format("Y-m-d"))
                        ->whereDate('end_at', '<=', $startDate->format("Y-m-d"))
                        ->whereIn('timeoff_policy_id', $timeoffPolicyIds?->toArray());
                })
                ->whereDoesntHave('timeoffQuotas', function ($q) use ($timeoffPolicyId, $fourMonthsAgo) {
                    $q->where('timeoff_policy_id', $timeoffPolicyId)
                        ->whereRaw('DATE_ADD(effective_start_date, INTERVAL 4 MONTH) > ?', [$this->today]);
                    // ->whereRaw('DATE_ADD(effective_start_date, INTERVAL 4 MONTH) > ?', [$fourMonthsAgo->format('Y-m-d')]);
                })
                ->get(['id', 'company_id', 'name', 'join_date', 'type']);

            // dd($availableUsers?->toArray());
            dump($availableUsers?->pluck('id')->toArray());
            $users = collect([]);
            foreach ($availableUsers as $user) {
                $attendances = Attendance::query()->where('user_id', $user->id)
                    ->where(
                        fn($q) =>
                        $q->whereHas('details', fn($q) => $q->approved())
                            ->orWhereHas('timeoff', fn($q) => $q->approved())
                    )
                    ->with([
                        'shift' => fn($q) => $q->withTrashed()->selectMinimalist(),
                        'clockIn' => fn($q) => $q->approved(),
                        'clockOut' => fn($q) => $q->approved(),
                    ])
                    ->whereDateBetween($fourMonthsAgo, $startDate)
                    ->get(['id', 'schedule_id', 'shift_id', 'date', 'timeoff_id']);

                $isBreak = false;
                foreach ($dateRange as $date) {
                    $totalLate = 0;
                    $todaySchedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'is_overide_national_holiday', 'is_overide_company_holiday'], ['id', 'is_dayoff', 'clock_in', 'clock_out']);

                    $date = $date->format('Y-m-d');

                    $isHoliday = false;
                    if ($todaySchedule?->is_overide_company_holiday == false) {
                        $companyHoliday = $companyHolidays->first(function ($ch) use ($date) {
                            return date('Y-m-d', strtotime($ch->start_at)) <= $date && date('Y-m-d', strtotime($ch->end_at)) >= $date;
                        });

                        if ($companyHoliday) {
                            $isHoliday = true;
                        }
                    }

                    if ($todaySchedule?->is_overide_national_holiday == false) {
                        $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                            return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
                        });

                        if ($nationalHoliday) {
                            $isHoliday = true;
                        }
                    }

                    if ($isHoliday) continue;

                    if ($todaySchedule?->shift?->is_dayoff == true) continue;

                    $attendance = $attendances->firstWhere('date', $date);

                    if (!$attendance) {
                        $isBreak = true;
                        // dump($user->toArray());
                        // dump($date);
                        // dump($todaySchedule?->toArray());
                        // dd($attendance?->toArray());
                        break;
                    }

                    if ($attendance->timeoff_id) continue;

                    if (!$attendance->clockIn || !$attendance->clockOut) {
                        $isBreak = true;
                        // dump($user->toArray());
                        // dump($date);
                        // dump($todaySchedule?->toArray());
                        // dd($attendance->toArray());
                        break;
                    }

                    $attendanceClockIn = Carbon::parse($attendance->clockIn->time);
                    $scheduleClockIn = Carbon::parse($attendanceClockIn->format('Y-m-d') . ' ' . $todaySchedule->shift->clock_in);
                    if ($attendanceClockIn->greaterThan($scheduleClockIn)) {
                        $totalLate += abs($attendanceClockIn->diffInMinutes($scheduleClockIn));
                        if ($totalLate > 10) {
                            $isBreak = true;
                            dump($user->toArray());
                            dump($date);
                            dump($scheduleClockIn);
                            dump($attendanceClockIn);
                            dump($totalLate);
                            dump($todaySchedule?->toArray());
                            dd($attendance->toArray());
                            break;
                        }
                    }

                    $attendanceClockOut = Carbon::parse($attendance->clockOut->time);
                    $scheduleClockOut = Carbon::parse($attendanceClockOut->format('Y-m-d') . ' ' . $todaySchedule->shift->clock_out);
                    if ($attendanceClockOut->lessThan($scheduleClockOut)) {
                        $totalLate += abs($attendanceClockOut->diffInMinutes($scheduleClockOut));

                        if ($totalLate > 10) {
                            $isBreak = true;
                            dump($user->toArray());
                            dump($date);
                            dump($scheduleClockOut);
                            dump($attendanceClockOut);
                            dump($totalLate);
                            dump($todaySchedule?->toArray());
                            dd($attendance->toArray());
                            break;
                        }
                    }
                }

                if ($isBreak) continue;
                $users->push($user);
            }
            dd($users?->pluck('name')->toArray());

            foreach ($users as $user) {
                $timeoffQuota = $user->timeoffQuotas()->create([
                    'timeoff_policy_id' => $timeoffPolicyId,
                    'effective_start_date' => $startDate->format('Y-m-d'),
                    'effective_end_date' => $effectiveEndDate,
                    'quota' => 1,
                ]);

                $timeoffQuota->timeoffQuotaHistories()->create([
                    'user_id' => $timeoffQuota->user_id,
                    'is_automatic' => 1,
                    'is_increment' => true,
                    'new_balance' => $timeoffQuota->quota,
                    'description' => $description,
                ]);
            }
        }
    }
}
