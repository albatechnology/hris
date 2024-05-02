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
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\View\View;

class AttendanceReport implements FromView
{
    public function __construct(private ExportReportRequest $request)
    {
    }
    /**
     * @return array
     */
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
                    'shift' => fn ($q) => $q->select('id', 'name', 'clock_in', 'clock_out'),
                    'timeoff.timeoffPolicy',
                    'clockIn' => fn ($q) => $q->approved(),
                    'clockOut' => fn ($q) => $q->approved(),
                ])
                ->whereDateBetween($startDate, $endDate)
                ->get();

            $dataAttendance = [];
            foreach ($dateRange as $date) {
                $schedule = ScheduleService::getTodaySchedule($user, $date->format('Y-m-d'), ['id', 'name']);
                $attendance = $attendances->firstWhere('date', $date->format('Y-m-d'));
                if ($attendance) {
                    $shift = $attendance->shift;

                    // load overtime
                    $totalOvertime = AttendanceService::getSumOvertimeDuration($user, $date);
                    $attendance->total_overtime = $totalOvertime;

                    // load task
                    $totalTask = TaskService::getSumDuration($user, $date);
                    $attendance->total_task = $totalTask;
                } else {
                    $shift = $schedule->shift;
                }
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

            $data[] = [
                'user' => $user,
                'attendances' => $dataAttendance,
                'summary' => $summary ?? null
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
