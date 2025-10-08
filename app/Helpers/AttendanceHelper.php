<?php

namespace App\Helpers;

use App\Enums\ApprovalStatus;
use App\Enums\ProrateSetting;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceHelper
{
    public static function getTotalAttendance(User|int $user, Carbon | string $startDate, Carbon | string $endDate, ?string $attendanceStartDate = null): array
    {
        if (!$user instanceof User) {
            $user = User::findOrFail($user);
        }

        if (!($startDate instanceof Carbon)) {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }

        if (!($endDate instanceof Carbon)) {
            $endDate = Carbon::parse($endDate)->startOfDay();
        }

        if ($attendanceStartDate && !($attendanceStartDate instanceof Carbon)) {
            $attendanceStartDate = Carbon::parse($attendanceStartDate);
        } else {
            $attendanceStartDate = $startDate;
        }


        $dateRange = CarbonPeriod::create($startDate, $endDate);

        // $isFirstTimePayroll = PayrollHelper::isFirstTimePayroll($user);
        // if (!$isFirstTimePayroll) {
        //     $joinDate = Carbon::parse($user->join_date);
        //     if ($joinDate->between($startDate, $endDate)) {
        //         $startDate = $joinDate;
        //     }
        // }

        // $isCountNationalHoliday = false;
        // if ($payrollSetting->prorate_setting->in([ProrateSetting::BASE_ON_WORKING_DAY, ProrateSetting::CUSTOM_ON_WORKING_DAY])) {
        //     $isCountNationalHoliday = $payrollSetting->prorate_national_holiday_as_working_day ?? false;
        // }

        $attendances = Attendance::select([
            'id',
            'schedule_id',
            'shift_id',
            'timeoff_id',
            'date',
        ])
            ->where('user_id', $user->id)
            ->where(
                fn($q) => $q->whereHas('details', fn($q) => $q->approved())->orHas('timeoff')
            )
            ->whereDate('date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('date', '<=', $endDate->format('Y-m-d'))
            ->with([
                'clockIn' => fn($q) => $q->approved()->select('id', 'attendance_id'),
                'clockOut' => fn($q) => $q->approved()->select('id', 'attendance_id'),
                'timeoff' => fn($q) => $q->select('id', 'is_cancelled'),
            ])
            ->get(['id', 'date', 'timeoff_id']);

        $totalPresentDates = [];
        $totalWorkingDayDates = [];
        foreach ($dateRange as $date) {
            $totalPresentWasAdded = false;
            $totalWorkingDayWasAdded = false;
            $date = $date->format("Y-m-d");

            $todaySchedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'is_overide_national_holiday', 'is_overide_company_holiday', 'effective_date'], ['id', 'is_dayoff']);

            if (!$todaySchedule?->shift) {
                continue;
            }

            $attendanceOnDate = $attendances->firstWhere('date', $date);

            if ($todaySchedule?->shift->is_dayoff) {
                continue;
            }

            if ($attendanceStartDate->greaterThan($date)) {
                $totalPresentWasAdded = true;
            }

            $nationalHoliday = Event::whereNationalHoliday()
                ->where('company_id', $user->company_id)
                ->where(
                    fn($q) => $q->whereDate('start_at', '<=', $date)
                        ->whereDate('end_at', '>=', $date)
                )
                ->exists();

            // jika nationalHoliday dan schedule tidak termasuk holiday maka dianggap masuk
            if ($nationalHoliday && $todaySchedule->is_overide_national_holiday == false) {
                // $totalPresentDates[$date] = $date;
                continue;
            }


            $totalWorkingDayDates[$date] = $date;

            if ($attendanceOnDate?->timeoff && $attendanceOnDate->timeoff->approval_status == ApprovalStatus::APPROVED->value && $attendanceOnDate->timeoff->is_cancelled == false) {
                if (!$totalPresentWasAdded) {
                    $totalPresentDates[$date] = $date;
                    continue;
                }
            }

            if ($attendanceOnDate?->clockIn && $attendanceOnDate?->clockOut) {
                if (!$totalPresentWasAdded) {
                    $totalPresentDates[$date] = $date;
                    continue;
                }
            }
        }

        return [
            "total_present" => count($totalPresentDates),
            "total_working_days" => count($totalWorkingDayDates),
            "total_present_dates" => $totalPresentDates,
            "total_working_days_dates" => $totalWorkingDayDates,
            // "total_alpa" => $totalAlpa,
        ];
    }
    // getTotalAttend()

}
