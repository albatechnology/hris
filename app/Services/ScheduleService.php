<?php

namespace App\Services;

use App\Enums\ScheduleType;
use App\Models\Event;
use App\Models\NationalHoliday;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use DateTime;

class ScheduleService
{
    /**
     * get user today schedule.
     */
    public static function getTodaySchedule(?User $user = null, $datetime = null, array $scheduleColumn = [], array $shiftColumn = [], string $scheduleType = ScheduleType::ATTENDANCE->value)
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        $datetime = is_null($datetime) ? date('Y-m-d H:i:00') : date('Y-m-d H:i:00', strtotime($datetime));
        list($date, $time) = explode(' ', $datetime);

        /** @var Schedule $schedule */
        if ($scheduleType == ScheduleType::ATTENDANCE->value) {
            $schedule = $user->schedules()
                ->select(count($scheduleColumn) > 0 ? [...$scheduleColumn, 'effective_date'] : ['*'])
                ->where('type', $scheduleType)
                ->whereDate('effective_date', '<=', $date)
                ->withCount('shifts')
                ->orderByDesc('effective_date')->first();
        } else {
            $schedule = $user->userPatrolSchedules()->whereHas('schedule', function ($q) use ($scheduleType, $date) {
                $q->where('type', $scheduleType);
                $q->whereDate('effective_date', '<=', $date);
            })->first()?->schedule()
                ->select(count($scheduleColumn) > 0 ? [...$scheduleColumn, 'effective_date'] : ['*'])
                ->where('type', $scheduleType)
                ->whereDate('effective_date', '<=', $date)
                ->withCount('shifts')
                ->orderByDesc('effective_date')->first();
        }

        if (!$schedule || $schedule->shifts_count === 0) return null;

        $startDate = new DateTime($schedule->effective_date);
        $endDate = new DateTime($date);
        $interval = $startDate->diff($endDate)->days + 1;
        $order = $interval % $schedule->shifts_count;
        $order = $order > 0 ? $order : $schedule->shifts_count;
        $previousOrder = ($order - 1) == 0 ? $schedule->shifts_count : ($order - 1);

        unset($schedule->pivot);

        if ($scheduleType == ScheduleType::PATROL->value) {
            $result = $schedule->load(['shift' => fn($q) => $q->select(count($shiftColumn) > 0 ? $shiftColumn : ['*'])->where('order', $order)->where('clock_in', '<=', date('H:i:s'))->where('clock_out', '>=', date('H:i:s'))]);
        } else {
            // check if shift accross the day
            $result = $schedule->load([
                'shift' => fn($q) => $q->select(count($shiftColumn) > 0 ? $shiftColumn : ['*'])
                    ->where('order', $previousOrder)
                    ->whereTime('clock_out', '<', 'clock_in')
            ]);

            // if shift accross the day not found. use today shift
            if ($result->shift) {
                $clockInStrtotime = strtotime(date('Y-m-d ' . $result->shift->clock_in, strtotime('-1 day')));
                $clockOutStrtotime = strtotime($result->shift->clock_out);

                // toleransi clockout based on config, default 2 hours
                $clockOutToleransiStrtotime = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d ' . $result->shift->clock_out) . sprintf('+%s hours', config('app.clock_out_tolerance')))));

                // cek apakah jam saat clockout masih di range shift atau kurang dari 2 jam dari clockout shift nya
                // kalo iya berarti clockout di shift tersebut. else pake shift hari ini (shift berdasarkan tgl)
                $timeStrtotime = strtotime($time);
                if (($timeStrtotime <= $clockOutStrtotime && $timeStrtotime >= $clockInStrtotime) || $timeStrtotime <= $clockOutToleransiStrtotime) {
                    return $result;
                }
            }

            $result = $schedule->load([
                'shift' => fn($q) => $q->select(count($shiftColumn) > 0 ? $shiftColumn : ['*'])->where('order', $order)
            ]);
        }

        return $result;
    }

    /**
     * Check the availability of user's schedule within a given date range.
     *
     * @param  User|null  $user  The user for whom the schedule availability is being checked.
     * @param  mixed  $startDate  The effective_date for the schedule availability check.
     * @param  mixed  $endDate  The effective_date for the schedule availability check.
     */
    public static function checkAvailableSchedule(?User $user = null, $startDate = null, $endDate = null): bool
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        $startDate = is_null($startDate) ? date('Y-m-d') : date('Y-m-d', strtotime($startDate));
        $endDate = is_null($endDate) ? $startDate : date('Y-m-d', strtotime($endDate));

        return $user->schedules()->whereDate('effective_date', '<=', $startDate)->orWhereDate('effective_date', '<=', $endDate)->orderByDesc('effective_date')->exists();
    }

    public static function getTotalWorkingDaysInPeriod(User $user, string|DateTime $startPeriod, string|DateTime $endPeriod): int
    {
        $totalDays = 0;
        $startPeriod = Carbon::createFromFormat('Y-m-d', $startPeriod)->addDay();
        $endPeriod = Carbon::createFromFormat('Y-m-d', $endPeriod);
        $dateRange = \Carbon\CarbonPeriod::create($startPeriod, $endPeriod);

        $companyHolidays = Event::tenanted($user)->whereHoliday()->get(['id', 'start_at', 'end_at']);
        $nationalHolidays = NationalHoliday::orderBy('date')->get(['id', 'date']);

        foreach ($dateRange as $date) {
            $schedule = ScheduleService::getTodaySchedule(
                $user,
                $date,
                ['id', 'is_overide_national_holiday', 'is_overide_national_holiday', 'is_overide_company_holiday'],
                ['id', 'is_dayoff']
            );

            $companyHolidayData = null;
            if ($schedule->is_overide_company_holiday == false) {
                $companyHolidayData = $companyHolidays->first(function ($companyHoliday) use ($date) {
                    return date('Y-m-d', strtotime($companyHoliday->start_at)) <= $date && date('Y-m-d', strtotime($companyHoliday->end_at)) >= $date;
                });
            }

            $nationalHoliday = null;
            if ($schedule->is_overide_national_holiday == false && is_null($companyHolidayData)) {
                $nationalHoliday = $nationalHolidays->firstWhere('date', $date);
            }

            if (is_null($companyHolidayData) && is_null($nationalHoliday) && !$schedule->shift->is_dayoff) {
                $totalDays += 1;
            }
        }

        return $totalDays;
    }
}
