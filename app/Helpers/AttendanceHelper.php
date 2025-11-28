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
    public static function getTotalAttendanceForPayroll(PayrollSetting $payrollSetting, User|int $user, Carbon | string $startDate, Carbon | string $endDate, ?string $attendanceStartDate = null): array
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

        $totalPresentDates = [];
        $totalWorkingDayDates = [];
        if ($payrollSetting->prorate_setting->is(ProrateSetting::BASE_ON_CALENDAR_DAY)) {
            // Konversi date range ke array [tanggal => tanggal]
            $totalWorkingDayDates = collect($dateRange)
                ->mapWithKeys(fn($date) => [$date->toDateString() => $date->toDateString()])
                ->toArray();
            // Ambil tanggal terakhir dari date range
            $lastDate = $dateRange->last();

            // Buat range baru dari attendanceStartDate sampai akhir date range
            // $dateRange is equal to user $totalPresentDates
            $dateRange = collect(CarbonPeriod::create($attendanceStartDate, $lastDate))
                ->mapWithKeys(fn($date) => [$date->toDateString() => $date->toDateString()])
                ->toArray();
        }

        $isBaseOnCalendarDayAndNotIgnoreAlpa = $payrollSetting->prorate_setting->is(ProrateSetting::BASE_ON_CALENDAR_DAY) && !$user->payrollInfo->is_ignore_alpa && !($user->company?->is_roster ?? false);
        $isRosterBaseOnCalendaryDay = $payrollSetting->prorate_setting->is(ProrateSetting::BASE_ON_CALENDAR_DAY) && ($user->company?->is_roster ?? false);
        if (
            $payrollSetting->prorate_setting->is(ProrateSetting::BASE_ON_WORKING_DAY)
            || ($isBaseOnCalendarDayAndNotIgnoreAlpa)
        ) {
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

            $isDateRangeInstanceCarbonPeriod = $dateRange instanceof CarbonPeriod;
            foreach ($dateRange as $date) {
                $totalPresentWasAdded = false;
                $totalWorkingDayWasAdded = false;
                if ($isDateRangeInstanceCarbonPeriod) {
                    $date = $date->format("Y-m-d");
                }

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
                    $totalWorkingDayWasAdded = true;
                }

                // only for BASE_ON_WORKING_DAY
                if (!$totalWorkingDayWasAdded && !$payrollSetting->prorate_setting->is(ProrateSetting::BASE_ON_CALENDAR_DAY)) {
                    $totalWorkingDayDates[$date] = $date;
                }

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
        } elseif ($payrollSetting->prorate_setting->is(ProrateSetting::BASE_ON_CALENDAR_DAY) && $user->payrollInfo->is_ignore_alpa) {
            $totalPresentDates = $dateRange;
        }elseif($isRosterBaseOnCalendaryDay){
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

            $isDateRangeInstanceCarbonPeriod = $dateRange instanceof CarbonPeriod;
            foreach ($dateRange as $date) {
                $totalPresentWasAdded = false;
                $totalWorkingDayWasAdded = false;
                if ($isDateRangeInstanceCarbonPeriod) {
                    $date = $date->format("Y-m-d");
                }

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
                    $totalWorkingDayWasAdded = true;
                }

                // only for BASE_ON_WORKING_DAY
                if (!$totalWorkingDayWasAdded && !$payrollSetting->prorate_setting->is(ProrateSetting::BASE_ON_CALENDAR_DAY)) {
                    $totalWorkingDayDates[$date] = $date;
                }

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
        }

        return [
            "total_present" => count($totalPresentDates),
            "total_working_days" => count($totalWorkingDayDates),
            "total_present_dates" => $totalPresentDates,
            "total_working_days_dates" => $totalWorkingDayDates,
            // "total_alpa" => $totalAlpa,
        ];
    }

    // public static function getTotalAttendance(User|int $user, Carbon | string $startDate, Carbon | string $endDate, ?string $attendanceStartDate = null): array
    // {
    //     if (!$user instanceof User) {
    //         $user = User::findOrFail($user);
    //     }

    //     if (!($startDate instanceof Carbon)) {
    //         $startDate = Carbon::parse($startDate)->startOfDay();
    //     }

    //     if (!($endDate instanceof Carbon)) {
    //         $endDate = Carbon::parse($endDate)->startOfDay();
    //     }

    //     if ($attendanceStartDate && !($attendanceStartDate instanceof Carbon)) {
    //         $attendanceStartDate = Carbon::parse($attendanceStartDate);
    //     } else {
    //         $attendanceStartDate = $startDate;
    //     }

    //     $dateRange = CarbonPeriod::create($startDate, $endDate);

    //     $attendances = Attendance::select([
    //         'id',
    //         'schedule_id',
    //         'shift_id',
    //         'timeoff_id',
    //         'date',
    //     ])
    //         ->where('user_id', $user->id)
    //         ->where(
    //             fn($q) => $q->whereHas('details', fn($q) => $q->approved())->orHas('timeoff')
    //         )
    //         ->whereDate('date', '>=', $startDate->format('Y-m-d'))
    //         ->whereDate('date', '<=', $endDate->format('Y-m-d'))
    //         ->with([
    //             'clockIn' => fn($q) => $q->approved()->select('id', 'attendance_id'),
    //             'clockOut' => fn($q) => $q->approved()->select('id', 'attendance_id'),
    //             'timeoff' => fn($q) => $q->select('id', 'is_cancelled'),
    //         ])
    //         ->get(['id', 'date', 'timeoff_id']);

    //     $totalPresentDates = [];
    //     $totalWorkingDayDates = [];
    //     foreach ($dateRange as $date) {
    //         $totalPresentWasAdded = false;
    //         $totalWorkingDayWasAdded = false;
    //         $date = $date->format("Y-m-d");

    //         $todaySchedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'is_overide_national_holiday', 'is_overide_company_holiday', 'effective_date'], ['id', 'is_dayoff']);

    //         if (!$todaySchedule?->shift) {
    //             continue;
    //         }

    //         $attendanceOnDate = $attendances->firstWhere('date', $date);

    //         if ($todaySchedule?->shift->is_dayoff) {
    //             continue;
    //         }

    //         if ($attendanceStartDate->greaterThan($date)) {
    //             $totalPresentWasAdded = true;
    //         }

    //         $nationalHoliday = Event::whereNationalHoliday()
    //             ->where('company_id', $user->company_id)
    //             ->where(
    //                 fn($q) => $q->whereDate('start_at', '<=', $date)
    //                     ->whereDate('end_at', '>=', $date)
    //             )
    //             ->exists();

    //         // jika nationalHoliday dan schedule tidak termasuk holiday maka dianggap masuk
    //         // dump($nationalHoliday?->toArray());
    //         // if($nationalHoliday){
    //         //     dd($todaySchedule);
    //         // }
    //         if ($nationalHoliday && $todaySchedule->is_overide_national_holiday == false) {
    //             $totalWorkingDayWasAdded = true;
    //         }

    //         if (!$totalWorkingDayWasAdded) {
    //             $totalWorkingDayDates[$date] = $date;
    //         }

    //         if ($attendanceOnDate?->timeoff && $attendanceOnDate->timeoff->approval_status == ApprovalStatus::APPROVED->value && $attendanceOnDate->timeoff->is_cancelled == false) {
    //             if (!$totalPresentWasAdded) {
    //                 $totalPresentDates[$date] = $date;
    //                 continue;
    //             }
    //         }

    //         if ($attendanceOnDate?->clockIn && $attendanceOnDate?->clockOut) {
    //             if (!$totalPresentWasAdded) {
    //                 $totalPresentDates[$date] = $date;
    //                 continue;
    //             }
    //         }
    //     }

    //     return [
    //         "total_present" => count($totalPresentDates),
    //         "total_working_days" => count($totalWorkingDayDates),
    //         "total_present_dates" => $totalPresentDates,
    //         "total_working_days_dates" => $totalWorkingDayDates,
    //         // "total_alpa" => $totalAlpa,
    //     ];
    // }
}
