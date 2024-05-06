<?php

namespace App\Exports;

use App\Http\Requests\Api\Attendance\ExportReportRequest;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\NationalHoliday;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ScheduleService;
use App\Services\TaskService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class AttendanceReport implements FromView
{
    public function __construct(private ExportReportRequest $request)
    {
    }
    /**
     * @return array
     */
    // public function view(): View
    public function view(): View
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $this->request->filter['start_date']);
        $endDate = Carbon::createFromFormat('Y-m-d', $this->request->filter['end_date']);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $companyHolidays = Event::tenanted()->whereHoliday()->get();
        $nationalHolidays = NationalHoliday::orderBy('date')->get();

        $users = User::tenanted(true)
            ->where('join_date', '<=', $startDate)
            ->where(fn ($q) => $q->whereNull('resign_date')->orWhere('resign_date', '>=', $endDate))
            ->get(['id', 'name', 'nik', 'resign_date']);

        $data = [];
        foreach ($users as $user) {
            $user->setAppends([]);
            $attendances = Attendance::where('user_id', $user->id)
                ->with([
                    'shift' => fn ($q) => $q->select('id', 'name', 'is_dayoff', 'clock_in', 'clock_out'),
                    'timeoff.timeoffPolicy',
                    'clockIn' => fn ($q) => $q->approved(),
                    'clockOut' => fn ($q) => $q->approved(),
                ])
                ->whereDateBetween($startDate, $endDate)
                ->get();

            $dataAttendance = [];
            foreach ($dateRange as $date) {
                $schedule = ScheduleService::getTodaySchedule($user, $date->format('Y-m-d'), ['id', 'name'], ['id', 'name', 'is_dayoff', 'clock_in', 'clock_out']);
                $attendance = $attendances->firstWhere('date', $date->format('Y-m-d'));
                if ($attendance?->clockIn) {
                    $attendance->clock_in = $attendance?->clockIn;
                }
                if ($attendance?->clockOut) {
                    $attendance->clock_out = $attendance?->clockOut;
                }
                if ($attendance?->timeoff) {
                    $attendance->clock_out = $attendance?->timeoff;
                    if ($attendance->timeoff->timeoffPolicy) {
                        $attendance->timeoff->timeoffPolicy = $attendance?->timeoff->timeoffPolicy;
                    }
                }
                if ($attendance) {
                    $shift = $attendance->shift;

                    // load overtime
                    $overtimeDurationBeforeShift = AttendanceService::getSumOvertimeDuration(user: $user, startDate: $date, formatText: false, query: fn ($q) => $q->where('is_after_shift', false));
                    $attendance->overtime_duration_before_shift = $overtimeDurationBeforeShift;

                    $overtimeDurationAfterShift = AttendanceService::getSumOvertimeDuration(user: $user, startDate: $date, formatText: false, query: fn ($q) => $q->where('is_after_shift', true));
                    $attendance->overtime_duration_after_shift = $overtimeDurationAfterShift;

                    // load task
                    $totalTask = TaskService::getSumDuration($user, $date);
                    $attendance->total_task = $totalTask;

                    if ($attendance->clockIn) {
                        $attendance->late_in = getIntervalTime($attendance->shift->clock_in, date('H:i:s', strtotime($attendance->clockIn->time)), true);
                    }

                    if ($attendance->clockOut) {
                        $attendance->early_out = getIntervalTime(date('H:i:s', strtotime($attendance->clockOut->time)), $attendance->shift->clock_out, true);
                    }

                    if ($attendance->clockIn && $attendance->clockOut) {
                        $attendance->real_working_hour = getIntervalTime(date('H:i:s', strtotime($attendance->clockIn->time)), date('H:i:s', strtotime($attendance->clockOut->time)));
                    }
                } else {
                    $shift = $schedule->shift;
                }
                $shift->schedule_working_hour = getIntervalTime($shift?->clock_in, $shift?->clock_out);
                $shiftType = 'shift';

                $companyHolidayData = null;
                if ($schedule->is_overide_company_holiday == false) {
                    $companyHolidayData = $companyHolidays->first(function ($companyHoliday) use ($date) {
                        return date('Y-m-d', strtotime($companyHoliday->start_at)) <= $date && date('Y-m-d', strtotime($companyHoliday->end_at)) >= $date;
                    });

                    if ($companyHolidayData) {
                        $shift = $companyHolidayData;
                        $shiftType = 'company_holiday';
                    }
                }

                if ($schedule->is_overide_national_holiday == false && is_null($companyHolidayData)) {
                    $nationalHoliday = $nationalHolidays->firstWhere('date', $date);
                    if ($nationalHoliday) {
                        $shift = $nationalHoliday;
                        $shiftType = 'national_holiday';
                    }
                }

                unset($shift->pivot);

                $dataAttendance[] = [
                    // 'user' => $user,
                    'date' => $date,
                    'shift_type' => $shiftType,
                    'shift' => $shift,
                    'attendance' => $attendance
                ];
            }
            $dataAttendance = collect($dataAttendance);

            $data[] = [
                'user' => $user,
                'attendances' => $dataAttendance,
                'summary' => [
                    'late_in' => sumTimes($dataAttendance->pluck('attendance.late_in')),
                    'early_out' => sumTimes($dataAttendance->pluck('attendance.early_out')),
                    'schedule_working_hour' => sumTimes($dataAttendance->pluck('shift.schedule_working_hour')),
                    'real_working_hour' => sumTimes($dataAttendance->pluck('attendance.real_working_hour')),
                    'overtime_duration_before_shift' => sumTimes($dataAttendance->pluck('attendance.overtime_duration_before_shift')),
                    'overtime_duration_after_shift' => sumTimes($dataAttendance->pluck('attendance.overtime_duration_after_shift')),
                ]
            ];
        }

        return view('api.exports.report-attendance', [
            'data' => $data
        ]);
    }

    // public function headings(): array
    // {
    //     return [
    //         'NIK',
    //         'Name',
    //         'Date',
    //         'Shift',
    //         'Schedule Check In',
    //         'Schedule Check Out',
    //         'Attendance Code',
    //         'Time Off Code',
    //         'Check In',
    //         'Check Out',
    //         'Late In',
    //         'Early Out',
    //         'Schedule Working Hour',
    //         'Actual Working Hour',
    //         'Real Working Hour',
    //         'Overtime Duration Before',
    //         'Overtime Duration After',
    //     ];
    // }
}
