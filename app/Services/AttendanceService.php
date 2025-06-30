<?php

namespace App\Services;

use App\Enums\DailyAttendance;
use App\Enums\EventType;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\Event;
use App\Models\LockAttendance;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceService
{
    public static function getTodayAttendance(?string $date = null, ?int $scheduleId = null, ?int $shiftId = null, ?User $user = null, $isCheckByDetails = true): ?Attendance
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
            ->get(['real_duration']);

        if ($overtimeRequests->count() <= 0) return null;

        $totalSeconds = 0;
        foreach ($overtimeRequests as $overtimeRequest) {
            // $startAt = new \DateTime($overtimeRequest->start_at);
            // $endAt = new \DateTime($overtimeRequest->end_at);
            // $interval = $startAt->diff($endAt);

            // $totalSeconds += ((int)$interval->format('%d') * 3600 * 24) + ((int)$interval->format('%h') * 3600) + ((int)$interval->format('%s') * 60) + (int)$interval->format('%s');

            list($hours, $minutes, $seconds) = explode(':', $overtimeRequest->real_duration);
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
        /**
         * total kehadiran masih perlu diperbaiki.
         * misal absen di weekend(dayoff) tapi tidak ada shift?
         * bagaimana jika data attendance tsb adalah timeoff, maka ketika menggunakan valid() tidak akan terhitng.
         *
         */
        if (!$user instanceof User) {
            $user = User::find($user, ['id', 'type', 'group_id']);
        }

        $totalAttendance = Attendance::where('user_id', $user->id)->valid()
            ->whereDateBetween($startDate, $endDate)
            ->count();

        return $totalAttendance;
    }

    public static function getTotalWorkingDays(User|int $user, $startDate, $endDate): int
    {
        if (!$user instanceof User) {
            $user = User::find($user, ['id', 'type', 'group_id']);
        }

        if ($user->payrollInfo?->total_working_days > 0) {
            return $user->payrollInfo->total_working_days;
        }

        $startDate = Carbon::createFromDate($startDate);
        $endDate = Carbon::createFromDate($endDate);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        // $companyHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereCompanyHoliday()->get();
        $nationalHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereNationalHoliday()->get();

        $totalWorkingDays = 0;
        foreach ($dateRange as $date) {
            $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name', 'is_overide_national_holiday', 'is_overide_company_holiday'], ['id', 'is_dayoff', 'name', 'clock_in', 'clock_out']);

            if (!$schedule || !$schedule->shift) {
                continue;
            }

            $totalWorkingDays++;

            if ($schedule->shift->is_dayoff) {
                $totalWorkingDays--;
                continue;
            };


            if (!$schedule->is_overide_national_holiday) {
                $date = $date->format('Y-m-d');
                $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                    return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
                });

                if ($nationalHoliday) {
                    $totalWorkingDays--;
                }
            }

            // if (!$schedule->shift->is_dayoff && !$schedule->is_overide_national_holiday && !$schedule->is_overide_company_holiday) {
            //     $totalWorkingDays++;
            //     continue;
            // };

            // if ($schedule->is_overide_national_holiday) {
            //     $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
            //         return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
            //     });

            //     if ($nationalHoliday) {
            //         $totalWorkingDays++;
            //     }
            // }
        }

        return $totalWorkingDays;
    }

    public static function getTotalWorkingDaysNewUser(User|int $user, $startDate, $endDate): int
    {
        if (!$user instanceof User) {
            $user = User::find($user, ['id', 'type', 'group_id']);
        }

        $startDate = Carbon::createFromDate($startDate);
        $endDate = Carbon::createFromDate($endDate);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        // $companyHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereCompanyHoliday()->get();
        $nationalHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereNationalHoliday()->get();

        $totalWorkingDays = 0;
        foreach ($dateRange as $date) {
            $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name', 'is_overide_national_holiday', 'is_overide_company_holiday'], ['id', 'is_dayoff', 'name', 'clock_in', 'clock_out']);

            if (!$schedule || !$schedule->shift) {
                continue;
            }

            $totalWorkingDays++;

            if (!$schedule->is_overide_national_holiday) {
                $date = $date->format('Y-m-d');
                $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                    return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
                });

                if ($nationalHoliday) {
                    continue;
                }
            }

            if (
                $schedule->shift->is_dayoff
                && (!isset($schedule->shift->is_request_shift))
            ) {
                $totalWorkingDays--;
            }
        }

        return $totalWorkingDays;
    }

    public static function getTotalAttend(User|int $user, Carbon | string $startDate, Carbon | string $endDate)
    {
        $userId = $user;
        if ($user instanceof User) {
            $userId = $user->id;
        }

        if (!($startDate instanceof Carbon)) {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }

        if (!($endDate instanceof Carbon)) {
            $endDate = Carbon::parse($endDate)->startOfDay();
        }

        $isFirstTimePayroll = RunPayrollService::isFirstTimePayroll($user);
        if (!$isFirstTimePayroll) {
            $joinDate = Carbon::parse($user->join_date);
            if ($joinDate->between($startDate, $endDate)) {
                $startDate = $joinDate;
            }
        }

        $dateRange = CarbonPeriod::create($startDate, $endDate);
        $attendances = Attendance::where('user_id', $userId)
            ->whereDate('date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('date', '<=', $endDate->format('Y-m-d'))
            ->with([
                'clockIn' => fn($q) => $q->approved()->select('id', 'attendance_id'),
                'clockOut' => fn($q) => $q->approved()->select('id', 'attendance_id'),
                'timeoff' => fn($q) => $q->select('id', 'is_cancelled'),
            ])
            ->get(['id', 'date', 'timeoff_id']);

        $totalAttend = 0;
        foreach ($dateRange as $date) {
            $todaySchedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'is_overide_national_holiday', 'is_overide_company_holiday', 'effective_date'], ['id', 'is_dayoff']);

            if (!$todaySchedule?->shift || $todaySchedule?->shift->is_dayoff) {
                continue;
            }

            $nationalHoliday = Event::whereNationalHoliday()
                ->where('company_id', $user->company_id)
                ->where(
                    fn($q) => $q->whereDate('start_at', '<=', $date->format('Y-m-d'))
                        ->whereDate('end_at', '>=', $date->format('Y-m-d'))
                )
                ->exists();
            if ($nationalHoliday && $todaySchedule->is_overide_national_holiday == false) {
                $totalAttend++;
                continue;
            }

            $attendanceOnDate = $attendances->firstWhere('date', $date->format('Y-m-d'));
            if ($attendanceOnDate?->clockIn && $attendanceOnDate?->clockOut) {
                $totalAttend++;
                continue;
            }
        }

        return $totalAttend;
    }

    public static function getTotalAlpa(User|int $user, Carbon | string $startDate, Carbon | string $endDate)
    {
        $userId = $user;
        if ($user instanceof User) {
            $userId = $user->id;
        }

        if (!($startDate instanceof Carbon)) {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }

        if (!($endDate instanceof Carbon)) {
            $endDate = Carbon::parse($endDate)->startOfDay();
        }

        $isFirstTimePayroll = RunPayrollService::isFirstTimePayroll($user);
        if (!$isFirstTimePayroll) {
            $joinDate = Carbon::parse($user->join_date);
            if ($joinDate->between($startDate, $endDate)) {
                $startDate = $joinDate;
            }
        }

        $dateRange = CarbonPeriod::create($startDate, $endDate);
        $attendances = Attendance::where('user_id', $userId)
            ->whereDate('date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('date', '<=', $endDate->format('Y-m-d'))
            ->with([
                'clockIn' => fn($q) => $q->approved()->select('id', 'attendance_id'),
                'clockOut' => fn($q) => $q->approved()->select('id', 'attendance_id'),
                'timeoff' => fn($q) => $q->select('id', 'is_cancelled'),
            ])
            ->get(['id', 'date', 'timeoff_id']);

        $totalAlpa = 0;
        foreach ($dateRange as $date) {
            $todaySchedule = ScheduleService::getTodaySchedule($user, $date, ['id'], ['id', 'is_dayoff']);
            if (!$todaySchedule?->shift) {
                $totalAlpa++;
                continue;
            }

            if ($todaySchedule?->shift->is_dayoff) {
                continue;
            }

            $nationalHoliday = Event::whereNationalHoliday()
                ->where('company_id', $user->company_id)
                ->where(
                    fn($q) => $q->whereDate('start_at', '<=', $date->format('Y-m-d'))
                        ->whereDate('end_at', '>=', $date->format('Y-m-d'))
                )
                ->exists();
            if ($nationalHoliday) continue;

            $attendanceOnDate = $attendances->firstWhere('date', $date->format('Y-m-d'));
            if (!$attendanceOnDate) {
                $totalAlpa++;
                continue;
            }

            if ($attendanceOnDate->timeoff && $attendanceOnDate->timeoff->is_cancelled == false) {
                continue;
            }

            if (!$attendanceOnDate->clockIn || !$attendanceOnDate->clockOut) {
                $totalAlpa++;
                continue;
            }

            // $attendanceClockIn = Carbon::parse($attendanceOnDate->clockIn->time);
            // $scheduleClockIn = Carbon::parse($attendanceClockIn->format('Y-m-d') . ' ' . $todaySchedule->shift->clock_in);
            // if ($attendanceClockIn->greaterThan($scheduleClockIn)) {
            //     $time = $attendanceClockIn->diffInMinutes($scheduleClockIn);
            //     $graceTotalLate += $time;
            //     if ($graceTotalLate > 10) {
            //         $totalLate += $time - 10;
            //     }
            // }

            // if ($totalLate > 10) {
            //     $totalAlpa++;
            //     continue;
            // }

            // $attendanceClockOut = Carbon::parse($attendanceOnDate->clockOut->time);
            // $scheduleClockOut = Carbon::parse($attendanceClockOut->format('Y-m-d') . ' ' . $todaySchedule->shift->clock_out);
            // if ($attendanceClockOut->lessThan($scheduleClockOut)) {
            //     $time = $attendanceClockOut->diffInMinutes($scheduleClockOut);
            //     $graceTotalLate += $time;
            //     if ($graceTotalLate > 10) {
            //         $totalLate += $time - max(10 - $graceTotalLate, 0);
            //     }
            // }

            // if ($totalLate > 10) {
            //     $totalAlpa++;
            //     continue;
            // }
        }

        return $totalAlpa;
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

        if ($shift->is_enable_grace_period === true) {
            if (($remainingTime + $realDiffInMinute) > $tolerance) {
                // $diffInMinutes += $remainingTime;
                $remainingTime = 0;
                $diffInTime = gmdate('H:i:s', $diffInSeconds);
            } else {
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

    public static function inLockAttendance(string $date, ?User $user = null): bool
    {
        if (config('app.name') != 'SUNSHINE') return false;

        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        return LockAttendance::whereCompany($user->company_id)
            ->whereDateIn($date)
            ->exists();
    }
}
