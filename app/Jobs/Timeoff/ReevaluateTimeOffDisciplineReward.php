<?php

namespace App\Jobs\Timeoff;

use App\Enums\TimeoffPolicyType;
use App\Enums\UserType;
use App\Jobs\Timeoff\CleanRemainingTimeoff;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Event;
use App\Models\Timeoff;
use App\Models\TimeoffPolicy;
use App\Models\TimeoffQuota;
use App\Models\User;
use App\Models\UserTimeoffHistory;
use App\Services\ScheduleService;
use App\Services\TimeoffRegulationService;
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

    public Carbon $startDate;
    public Carbon $endDate;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $endDate = null, ?string $startDate = null)
    {
        $this->endDate = $endDate ? Carbon::createFromFormat('Y-m-d', $endDate) : Carbon::now()->subMonth()->endOfMonth();
        $this->startDate = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate) : $this->endDate->copy()->subMonths(3)->startOfMonth();
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
        $today = $this->endDate;

        dump($fourMonthsAgo->format('Y-m-d'));
        dump($today->format('Y-m-d'));

        $effectiveEndDate = $today->month == 11 || $today->month == 12 ? $today->copy()->addYear()->endOfYear()->format('Y-m-d') : date('Y-12-31');
        $description = sprintf("AUTOMATICALLY GENERATED FROM THE SYSTEM (EO %s - %s)", $fourMonthsAgo->format('Y-m-d'), $today->format('Y-m-d'));

        $dateRange = CarbonPeriod::create($fourMonthsAgo, $today);

        $companies = Company::select('id')->where('id', 1)->get();

        foreach ($companies as $company) {
            $timeoffPolicyId = TimeoffPolicy::select('id')->where('type', TimeoffPolicyType::EXTRA_OFF)->firstOrFail()->id;
            $companyHolidays = Event::selectMinimalist()->where('company_id', $company->id)->whereDateBetween($fourMonthsAgo, $today)->whereCompanyHoliday()->get();
            $nationalHolidays = Event::selectMinimalist()->where('company_id', $company->id)->whereDateBetween($fourMonthsAgo, $today)->whereNationalHoliday()->get();

            $timeoffPolicyIds = TimeoffPolicy::where('company_id', $company->id)
                ->whereIn('type', [
                    TimeoffPolicyType::SICK_WITH_CERTIFICATE,
                    TimeoffPolicyType::SICK_WITHOUT_CERTIFICATE,
                    TimeoffPolicyType::UNPAID_LEAVE,
                    TimeoffPolicyType::MATERNITY_LEAVE,
                ])->get(['id']);

            $availableUsers = User::where('company_id', $company->id)
                ->whereNotIn('id', [125, 189])
                ->whereIn('id', [29,34,43,45,53,54,56,67,76,77,89,92,94,95,97,103,116,126,130,133,134,151,184,193,198,203,241])
                ->whereIn('type', [UserType::ADMINISTRATOR, UserType::USER])
                ->whereDoesntHave('timeoffs', function ($q) use ($fourMonthsAgo, $today, $timeoffPolicyIds) {
                    $q->approved()
                        ->whereDate('start_at', '>=', $fourMonthsAgo->format("Y-m-d"))
                        ->whereDate('end_at', '<=', $today->format("Y-m-d"))
                        ->whereIn('timeoff_policy_id', $timeoffPolicyIds?->toArray());
                })
                ->whereDoesntHave(
                    'timeoffQuotas',
                    fn($q) => $q->where('timeoff_policy_id', $timeoffPolicyId)
                        ->where(fn($q) => $q->whereDate('effective_end_date', '>=', $fourMonthsAgo->format('Y-m-d'))->orWhereDate('effective_end_date', '>=', $today->format('Y-m-d')))
                )
                ->get(['id', 'company_id', 'name', 'join_date', 'type']);

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
                    ->whereDateBetween($fourMonthsAgo, $today)
                    ->get(['id', 'schedule_id', 'shift_id', 'date', 'timeoff_id']);

                $isBreak = false;
                foreach ($dateRange as $date) {
                    $totalLate = 0;
                    $todaySchedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'is_overide_national_holiday', 'is_overide_company_holiday'], ['id', 'is_dayoff']);

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
                        dump($user->toArray());
                        dump($date);
                        dump($todaySchedule?->toArray());
                        dd($attendance);
                        break;
                    }

                    if ($attendance->timeoff_id) continue;

                    if (!$attendance->clockIn || !$attendance->clockOut) {
                        $isBreak = true;
                        dump($user->toArray());
                        dump($date);
                        dump($todaySchedule?->toArray());
                        dd($attendance);
                        break;
                    }

                    $attendanceClockIn = Carbon::parse($attendance->clockIn->time);
                    $scheduleClockIn = Carbon::parse($attendanceClockIn->format('Y-m-d') . ' ' . $todaySchedule->shift->clock_in);
                    if ($attendanceClockIn->greaterThan($scheduleClockIn)) {
                        $totalLate += $attendanceClockIn->diffInMinutes($scheduleClockIn);
                        if ($totalLate > 10) {
                            $isBreak = true;
                            dump($user->toArray());
                            dump($date);
                            dump($todaySchedule?->toArray());
                            dd($attendance);
                            break;
                        }
                    }

                    $attendanceClockOut = Carbon::parse($attendance->clockOut->time);
                    $scheduleClockOut = Carbon::parse($attendanceClockOut->format('Y-m-d') . ' ' . $todaySchedule->shift->clock_out);
                    if ($attendanceClockOut->lessThan($scheduleClockOut)) {
                        $totalLate += $attendanceClockOut->diffInMinutes($scheduleClockOut);
                        if ($totalLate > 10) {
                            $isBreak = true;
                            dump($user->toArray());
                            dump($date);
                            dump($todaySchedule?->toArray());
                            dd($attendance);
                            break;
                        }
                    }
                }

                if ($isBreak) continue;
                $users->push($user);
            }

            dump($users?->pluck('name')->toArray());

            foreach ($users as $user) {
                $timeoffQuota = $user->timeoffQuotas()->create([
                    'timeoff_policy_id' => $timeoffPolicyId,
                    'effective_start_date' => $today->format('Y-m-d'),
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
