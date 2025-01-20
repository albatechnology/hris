<?php

namespace App\Services;

use App\Enums\DailyAttendance;
use App\Enums\EventType;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    public static function getTodayAttendance(?string $date = null, int|string $scheduleId = null, int|string $shiftId = null, ?User $user = null, $isCheckByDetails = true): ?Attendance
    {
        /**
         *
         * kenapa ngecheck nya whereHas('details', fn($q) => $q->whereDate('time', $date)) ?
         * kenapa bukan where('date', $date) ?
         * hmmm masih menjadi misteri
         *
         * oke ganti dulu ke where('date', $date)
         */
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        $date = is_null($date) ? date('Y-m-d') : date('Y-m-d', strtotime($date));

        $attendance = Attendance::query()
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->when($scheduleId, fn($q) => $q->where('schedule_id', $scheduleId))
            ->when($shiftId, fn($q) => $q->where('shift_id', $shiftId))
            // ->where('schedule_id', $scheduleId)
            // ->where('shift_id', $shiftId)
            ->when(
                $isCheckByDetails,
                fn($q) => $q->whereHas('details', fn($q) => $q->whereDate('time', $date)),
                fn($q) => $q->whereDate('date', $date)
            )
            ->first();

        if (!$attendance) return null;

        return $attendance;
    }

    // public static function getTodayAttendance(int|string $scheduleId, int|string $shiftId, ?User $user = null, $date = null, $isCheckByDetails = true): ?Attendance
    // {
    //     /**
    //      *
    //      * kenapa ngecheck nya whereHas('details', fn($q) => $q->whereDate('time', $date)) ?
    //      * kenapa bukan where('date', $date) ?
    //      * hmmm masih menjadi misteri
    //      *
    //      * oke ganti dulu ke where('date', $date)
    //      */
    //     if (!$user) {
    //         /** @var User $user */
    //         $user = auth('sanctum')->user();
    //     }

    //     $date = is_null($date) ? date('Y-m-d') : date('Y-m-d', strtotime($date));

    //     $attendance = Attendance::where('schedule_id', $scheduleId)
    //         ->when($user, fn($q) => $q->where('user_id', $user->id))
    //         ->where('shift_id', $shiftId)
    //         ->when(
    //             $isCheckByDetails,
    //             fn($q) => $q->whereHas('details', fn($q) => $q->whereDate('time', $date)),
    //             fn($q) => $q->whereDate('date', $date)
    //         )
    //         ->first();

    //     if (!$attendance) {
    //         return null;
    //     }

    //     return $attendance;
    // }

    // public static function getSumOvertimeDuration(User|int $user, $date, OvertimeRequestType $requestType = null)
    public static function getSumOvertimeDuration(User|int $user, $startDate, $endDate = null, bool $formatText = true, callable $query = null)
    {
        if ($user instanceof User) {
            $user = $user->id;
        }

        $overtimeRequests = \App\Models\OvertimeRequest::tenanted()
            ->whereApprovalStatus(\App\Enums\ApprovalStatus::APPROVED)
            ->where('user_id', $user)
            ->when(
                is_null($endDate),
                fn($q) => $q->whereDate('date', $startDate),
                fn($q) => $q->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate)
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
    public static function getTotalPresent(User|int $user, $startDate, $endDate): int
    {
        if (!$user instanceof User) {
            $user = User::find($user, ['id', 'type', 'group_id']);
        }

        $totalAttendance = Attendance::where('user_id', $user->id)->valid()
            ->whereDateBetween($startDate, $endDate)
            ->count();

        return $totalAttendance;

        // if ($dailyAttendance == DailyAttendance::PRESENT) return $totalAttendance;

        // $totalWorkingDays = ScheduleService::getTotalWorkingDaysInPeriod($user, $startDate, $endDate);

        // return abs($totalAttendance - $totalWorkingDays);
    }

    public static function getTotalAlpa(User|int $user, $startDate, $endDate): int
    {
        if (!$user instanceof User) {
            $user = User::find($user, ['id', 'type', 'group_id']);
        }

        return ScheduleService::getTotalWorkingDaysInPeriod($user, $startDate, $endDate) - self::getTotalPresent($user, $startDate, $endDate);
    }

    public static function getTotalAttendanceInShifts(User|int $user, $startDate, $endDate, Shift|array $shifts = []): int
    {
        $shiftIds = [];
        if ($shifts instanceof Shift) {
            $shiftIds = [$shifts->id];
        } elseif (is_array($shifts) && count($shifts) > 0) {
            $shifts = collect($shifts);

            $shiftIds = $shifts->filter(fn($value) => is_numeric($value))->values()->toArray();

            $nationalHoliday = $shifts->filter(fn($value) => $value == 'national_holiday')->values()?->toArray()[0] ?? null;
            $companyHoliday = $shifts->filter(fn($value) => $value == 'company_holiday')->values()?->toArray()[0] ?? null;
        }

        $nationalHolidayDates = [];
        if (isset($nationalHoliday) && !is_null($nationalHoliday)) {
            $nationalHolidayDates = EventService::getDates(EventType::NATIONAL_HOLIDAY, $startDate, $endDate);
        }

        $companyHolidayDates = [];
        if (isset($companyHoliday) && !is_null($companyHoliday)) {
            $companyHolidayDates = EventService::getDates(EventType::COMPANY_HOLIDAY, $startDate, $endDate);
        }

        $totalAttendance = Attendance::where('user_id', $user->id)->valid()
            ->where(function ($q) use ($startDate, $endDate, $nationalHolidayDates, $companyHolidayDates) {
                $q->whereDateBetween($startDate, $endDate)
                    ->when(count($nationalHolidayDates), function ($q) use ($nationalHolidayDates) {
                        $q->orWhereIn('date', $nationalHolidayDates);
                    })
                    ->when(count($companyHolidayDates), function ($q) use ($companyHolidayDates) {
                        $q->orWhereIn('date', $companyHolidayDates);
                    });
            })
            ->whereIn('shift_id', $shiftIds)
            ->count();

        return $totalAttendance;
    }

    public static function getTotalLateTime(AttendanceDetail $attendanceDetail, Shift $shift, ?int $remainingTime = null): array
    {
        // dump('remainingTimeremainingTime', $remainingTime);
        /**
         *
         * dispensasi keterlambatan hanya berlaku jika is_enable_grace_period == true. selain itu dihitung terlambat
         * hitung waktu terlambat berdasarkan selisih waktu absen baik itu clock_in atau clock_out($endTime) dengan
         * jadwal masuk/clockin atau pulang/clockout ($startTime).
         *
         *
         *
         * clock_in_dispensation = 10 menit
         * clock_out_dispensation = 10 menit
         * time_dispensation = 10 menit
         * jadwal 09:00 - 18:00
         *
         * masuk jam 09:05
         * pulang jam 17:55 . $remainingTime 5 menitdiffInMinutes
         *
         */

        // dump($shift->toArray());
        // dump($attendanceDetail->toArray());

        $tolerance = $shift->time_dispensation;
        $endTime = Carbon::createFromFormat('H:i:s', date('H:i:s', strtotime($attendanceDetail->time)));

        if ($attendanceDetail->is_clock_in) {
            $startTime = Carbon::createFromFormat('H:i:s', $shift->clock_in);

            if ($endTime->lessThanOrEqualTo($startTime)) {
                $diffInSeconds = 0;
            } else {
                $diffInSeconds = $startTime->diffInSeconds($endTime);
            }
        } else {
            $startTime = Carbon::createFromFormat('H:i:s', $shift->clock_out);

            if ($endTime->lessThanOrEqualTo($startTime)) {
                $diffInSeconds = $endTime->diffInSeconds($startTime);
            } else {
                $diffInSeconds = 0;
            }
        }

        $remainingTime = $remainingTime && $remainingTime > 0 ? $remainingTime : 0;
        // $remainingTimeInSeconds = $remainingTime * 60;
        $diffInTime = "00:00:00";
        $realDiffInMinute = floor($diffInSeconds / 60);
        $diffInMinutes = $realDiffInMinute;
        // dump('remainingTime eee', $remainingTime);
        // dump($diffInSeconds);
        // dump('realDiffInMinute 1', $realDiffInMinute);

        if ($shift->is_enable_grace_period === true) {
            if (($remainingTime + $realDiffInMinute) > $tolerance) {
                // $diffInMinutes += $remainingTime;
                $remainingTime = 0;
                $diffInTime = gmdate('H:i:s', $diffInSeconds);
            } else {
                // dump('masuk sini');
                $remainingTime = $tolerance - ($remainingTime + $realDiffInMinute);
                $diffInMinutes = 0;
            }
        } else {
            $diffInTime = gmdate('H:i:s', $diffInSeconds);
        }

        return [
            $diffInMinutes, // real data
            $diffInTime,
            $remainingTime,
        ];
    }

    // public static function getTotalLateTime(AttendanceDetail $attendanceDetail, Shift $shift, bool $isFormatTime = true, ?int $remainingTime = null): array
    // {
    //     /**
    //      *
    //      * dispensasi keterlambatan hanya berlaku jika is_enable_grace_period == true. selain itu dihitung terlambat
    //      * hitung waktu terlambat berdasarkan selisih waktu absen baik itu clock_in atau clock_out($endTime) dengan
    //      * jadwal masuk/clockin atau pulang/clockout ($startTime).
    //      *
    //      *
    //      *
    //      * clock_in_dispensation = 10 menit
    //      * clock_out_dispensation = 10 menit
    //      * time_dispensation = 10 menit
    //      * jadwal 09:00 - 18:00
    //      *
    //      * masuk jam 09:05
    //      * pulang jam 17:55 . $remainingTime 5 menitdiffInMinutes
    //      *
    //      */

    //     dump($shift->toArray());
    //     dump($attendanceDetail->toArray());

    //     $remainingTime = $remainingTime && $remainingTime > 0 ? $remainingTime : 0;
    //     $endTime = Carbon::createFromFormat('H:i:s', date('H:i:s', strtotime($attendanceDetail->time)));

    //     if ($attendanceDetail->is_clock_in) {
    //         $tolerance = $shift->clock_in_dispensation;
    //         $startTime = Carbon::createFromFormat('H:i:s', $shift->clock_in);

    //         if ($endTime->lessThanOrEqualTo($startTime)) {
    //             $diffInSeconds = 0;
    //         } else {
    //             $diffInSeconds = $startTime->diffInSeconds($endTime);
    //         }
    //     } else {
    //         $tolerance = $shift->clock_out_dispensation;
    //         $startTime = Carbon::createFromFormat('H:i:s', $shift->clock_out);

    //         if ($endTime->lessThanOrEqualTo($startTime)) {
    //             $diffInSeconds = $endTime->diffInSeconds($startTime);
    //         } else {
    //             $diffInSeconds = 0;
    //         }
    //     }

    //     $diffInTime = "00:00:00";
    //     $diffInMinutes = floor($diffInSeconds / 60);
    //     dump($diffInSeconds);
    //     dump('diffInMinutes 1', $diffInMinutes);

    //     if ($shift->is_enable_grace_period === true) {
    //         $timeDispensation = $shift->time_dispensation <= 0 ? 0 : $shift->time_dispensation;
    //         $remainingTime = $timeDispensation <= 0 ? 0 : $timeDispensation - $diffInMinutes;
    //         dump($remainingTime);
    //         dump($timeDispensation);
    //         dump('remainingTime & timeDispensation');


    //         if ($diffInMinutes > $tolerance) {
    //             $diffInTime = gmdate('H:i:s', $diffInSeconds);
    //             $diffInMinutes = 0;
    //         }

    //         // if ($timeDispensation && $timeDispensation > 0) {
    //         //     $diffInMinutes = $timeDispensation - $diffInMinutes;
    //         // }

    //         // if ($isFormatTime === false) return $diffInMinutes > $tolerance ? $diffInMinutes : 0;
    //         // if ($isFormatTime === false) {
    //         //     $diffInMinutes = $diffInMinutes > $tolerance ? $diffInMinutes : 0;
    //         // }

    //         // if (!$timeDispensation && $timeDispensation > 0) {
    //         //     $diffInMinutes = $timeDispensation - $diffInMinutes;
    //         // }
    //         // dump('diffInMinutes 2', $diffInMinutes);

    //         // $diffInTime = "00:00:00";
    //         // dump('diffInTime 1', $diffInTime);
    //         // if ($diffInMinutes > $tolerance) {
    //         //     $diffInTime = gmdate('H:i:s', $diffInSeconds);
    //         // }
    //         // dump('diffInTime 2', $diffInTime);
    //     } else {
    //         $diffInTime = gmdate('H:i:s', $diffInSeconds);
    //     }

    //     return [
    //         $diffInMinutes, // real data
    //         $diffInTime,
    //         $remainingTime,
    //     ];
    // }
}
