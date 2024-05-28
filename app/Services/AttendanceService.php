<?php

namespace App\Services;

use App\Enums\DailyAttendance;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\NationalHoliday;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    public static function getTodayAttendance(int|string $scheduleId, int|string $shiftId, ?User $user = null, $date = null): ?Attendance
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        $date = is_null($date) ? date('Y-m-d') : date('Y-m-d', strtotime($date));

        $attendance = Attendance::where('schedule_id', $scheduleId)
            ->where('shift_id', $shiftId)
            ->whereHas('details', fn ($q) => $q->whereDate('time', $date))
            ->first();

        if (!$attendance) {
            return null;
        }

        return $attendance;
    }

    // public static function getSumOvertimeDuration(User|int $user, $date, OvertimeRequestType $requestType = null)
    public static function getSumOvertimeDuration(User|int $user, $startDate, $endDate = null, bool $formatText = true, callable $query = null)
    {
        if ($user instanceof User) {
            $user = $user->id;
        }

        $overtimeRequests = \App\Models\OvertimeRequest::tenanted()
            ->where('approval_status', \App\Enums\ApprovalStatus::APPROVED)
            ->where('user_id', $user)
            ->when(
                is_null($endDate),
                fn ($q) => $q->whereDate('date', $startDate),
                fn ($q) => $q->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate)
            )
            ->when($query, $query)
            ->get(['duration']);

        if ($overtimeRequests->count() <= 0) return null;

        $totalSeconds = 0;
        foreach ($overtimeRequests as $overtimeRequest) {
            // $startAt = new \DateTime($overtimeRequest->start_at);
            // $endAt = new \DateTime($overtimeRequest->end_at);
            // $interval = $startAt->diff($endAt);

            // $totalSeconds += ((int)$interval->format('%d') * 3600 * 24) + ((int)$interval->format('%h') * 3600) + ((int)$interval->format('%s') * 60) + (int)$interval->format('%s');

            list($hours, $minutes, $seconds) = explode(':', $overtimeRequest->duration);
            $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        // $seconds = $totalSeconds % 60;

        $result = '';
        if ($formatText) {
            if ((int)$hours > 0) {
                $result .= (int)$hours . 'h ';
            }
            if ((int)$minutes > 0) {
                $result .= (int)$minutes . 'm ';
            }
            // if ((int)$seconds > 0) {
            //     $result .= (int)$seconds . 's';
            // }
        } else {
            $result = sprintf("%02d:%02d:00", $hours, $minutes);
            // $result = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        }

        return trim($result);
    }

    /**
     * Calculates the total attendance(present/alpha) of a user within a given date range.
     *
     * @param User|int $user The user object or user ID.
     * @param string $startDate The start date of the date range in 'Y-m-d' format.
     * @param string $endDate The end date of the date range in 'Y-m-d' format.
     * @param DailyAttendance $dailyAttendance The attendance type to calculate (default: DailyAttendance::PRESENT).
     * @return int The total attendance(present/alpha) count.
     */
    public static function getTotalAttendance(User|int $user, $startDate, $endDate, DailyAttendance $dailyAttendance = DailyAttendance::PRESENT): int
    {
        if (!$user instanceof User) {
            $user = User::find($user, ['id', 'type', 'group_id']);
        }

        $totalAttendance = Attendance::where('user_id', $user->id)->valid()
            ->whereDateBetween($startDate, $endDate)
            ->count();

        if ($dailyAttendance == DailyAttendance::PRESENT) return $totalAttendance;

        $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->addDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $endDate);
        $dateRange = \Carbon\CarbonPeriod::create($startDate, $endDate);

        $companyHolidays = Event::tenanted($user)->whereHoliday()->get(['id', 'start_at', 'end_at']);
        $nationalHolidays = NationalHoliday::orderBy('date')->get(['id', 'date']);

        $totalAlpha = 0;
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
                $totalAlpha += 1;
            }
        }

        return abs($totalAttendance - $totalAlpha);
    }

    public static function getTotalAttendanceInShifts(User|int $user, $startDate, $endDate, Shift|array $shifts = []): int
    {
        $shiftIds = [];
        if ($shifts instanceof Shift) {
            $shiftIds = [$shifts->id];
        } elseif (is_array($shifts) && count($shifts) > 0) {
            $shiftIds = $shifts;
        }

        $totalAttendance = Attendance::where('user_id', $user->id)->valid()
            ->whereDateBetween($startDate, $endDate)
            ->whereIn('shift_id', $shiftIds)
            ->count();

        return $totalAttendance;
    }
}
