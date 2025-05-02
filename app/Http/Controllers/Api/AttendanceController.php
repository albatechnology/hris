<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceType;
use App\Enums\MediaCollection;
use App\Enums\ScheduleType;
use App\Events\Attendance\AttendanceRequested;
use App\Exports\AttendanceReport;
use App\Http\Requests\Api\Attendance\ChildrenRequest;
use App\Http\Requests\Api\Attendance\ExportReportRequest;
use App\Http\Requests\Api\Attendance\IndexRequest;
use App\Http\Requests\Api\Attendance\ManualAttendanceRequest;
use App\Http\Requests\Api\Attendance\RequestAttendanceRequest;
use App\Http\Requests\Api\Attendance\StoreRequest;
use App\Http\Requests\Api\BulkApproveRequest;
use App\Http\Requests\Api\NewApproveRequest;
use App\Http\Resources\Attendance\AttendanceApprovalsResource;
use App\Http\Resources\Attendance\AttendanceDetailResource;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Http\Resources\DefaultResource;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\DatabaseNotification;
use App\Models\Event;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\Aws\Rekognition;
use App\Services\ScheduleService;
use App\Services\TaskService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AttendanceController extends BaseController
{
    const ALLOWED_INCLUDES = ['user', 'schedule', 'shift', 'details'];

    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:attendance_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:attendance_access', ['only' => ['restore']]);
        $this->middleware('permission:attendance_read', ['only' => ['index', 'show', 'report', 'employees', 'employeesSummary']]);
        $this->middleware('permission:attendance_create', ['only' => ['store', 'request']]);
        $this->middleware('permission:attendance_edit', ['only' => 'update']);
        $this->middleware('permission:attendance_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function report(ExportReportRequest $request, ?string $export = null)
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $request->filter['start_date']);
        $endDate = Carbon::createFromFormat('Y-m-d', $request->filter['end_date']);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $branchId = isset($request['filter']['branch_id']) && !empty($request['filter']['branch_id']) ? $request['filter']['branch_id'] : null;
        $clientId = isset($request['filter']['client_id']) && !empty($request['filter']['client_id']) ? $request['filter']['client_id'] : null;
        $userIds = isset($request['filter']['user_ids']) && !empty($request['filter']['user_ids']) ? explode(',', $request['filter']['user_ids']) : null;

        $users = User::tenanted(true)
            ->where('join_date', '<=', $startDate)
            ->where(fn($q) => $q->whereNull('resign_date')->orWhere('resign_date', '>=', $endDate))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->get(['id', 'company_id', 'name', 'nik']);

        $data = [];
        foreach ($users as $user) {
            $companyHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereCompanyHoliday()->get();
            $nationalHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereNationalHoliday()->get();

            $user->setAppends([]);
            $attendances = Attendance::where('user_id', $user->id)
                ->with([
                    'shift' => fn($q) => $q->withTrashed()->selectMinimalist(['is_enable_grace_period', 'clock_in_dispensation', 'clock_out_dispensation', 'time_dispensation']),
                    'timeoff.timeoffPolicy',
                    'clockIn' => fn($q) => $q->approved(),
                    'clockOut' => fn($q) => $q->approved(),
                ])
                ->whereDateBetween($startDate, $endDate)
                ->get(['id', 'user_id', 'schedule_id', 'shift_id', 'timeoff_id', 'event_id', 'code', 'date']);

            $dataAttendance = [];
            foreach ($dateRange as $date) {
                $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name', 'is_overide_national_holiday', 'is_overide_company_holiday'], ['id', 'name', 'is_dayoff', 'clock_in', 'clock_out']);
                $attendance = $attendances->firstWhere('date', $date->format('Y-m-d'));

                if ($attendance?->clockIn) {
                    $attendance->clock_in = $attendance?->clockIn;
                }
                if ($attendance?->clockOut) {
                    $attendance->clock_out = $attendance?->clockOut;
                }
                if ($attendance?->timeoff) {
                    $attendance->timeoff = $attendance?->timeoff;
                    if ($attendance->timeoff->timeoffPolicy) {
                        $attendance->timeoff->timeoffPolicy = $attendance?->timeoff->timeoffPolicy;
                    }
                }

                if ($attendance) {
                    $shift = $attendance->shift;

                    // load overtime
                    $overtimeDurationBeforeShift = AttendanceService::getSumOvertimeDuration(user: $user, startDate: $date, formatText: false, query: fn($q) => $q->where('is_after_shift', false));
                    $attendance->overtime_duration_before_shift = $overtimeDurationBeforeShift;

                    $overtimeDurationAfterShift = AttendanceService::getSumOvertimeDuration(user: $user, startDate: $date, formatText: false, query: fn($q) => $q->where('is_after_shift', true));
                    $attendance->overtime_duration_after_shift = $overtimeDurationAfterShift;

                    // load task
                    $totalTask = TaskService::getSumDuration($user, $date);
                    $attendance->total_task = $totalTask;

                    // if (!$attendance->schedule->is_flexible && $attendance->schedule->is_include_late_in && $attendance->clockIn) {
                    $remainingTime = 0;
                    if ($attendance->clockIn) {
                        // $attendance->late_in = getIntervalTime($attendance->shift->clock_in, date('H:i:s', strtotime($attendance->clockIn->time)), true);
                        // $attendance->late_in = AttendanceService::getTotalLateTime($attendance->clockIn, $shift);
                        list($diffInMinute, $diffInTime, $remainingTime) = AttendanceService::getTotalLateTime($attendance->clockIn, $shift);
                        $attendance->late_in = $diffInTime;
                    }
                    // dump('remainingTime OKE', $remainingTime);
                    // if (!$attendance->schedule->is_flexible && $attendance->schedule->is_include_early_out && $attendance->clockOut) {
                    if ($attendance->clockOut) {
                        // dump('clockOut clockOut clockOut clockOut');
                        // $attendance->early_out = getIntervalTime(date('H:i:s', strtotime($attendance->clockOut->time)), $attendance->shift->clock_out, true);
                        // $attendance->early_out = AttendanceService::getTotalLateTime($attendance->clockOut, $shift);
                        list($diffInMinute, $diffInTime, $remainingTime) = AttendanceService::getTotalLateTime($attendance->clockOut, $shift, $remainingTime);
                        $attendance->early_out = $diffInTime;

                        if ($attendance->clockIn) {
                            list($diffInMinute, $diffInTime, $remainingTime) = AttendanceService::getTotalLateTime($attendance->clockIn, $shift, $diffInMinute);
                            $attendance->late_in = $diffInTime;
                        }
                    }

                    if ($attendance->clockIn && $attendance->clockOut) {
                        $attendance->real_working_hour = getIntervalTime(date('H:i:s', strtotime($attendance->clockIn->time)), date('H:i:s', strtotime($attendance->clockOut->time)));
                    }
                } else {
                    $shift = $schedule?->shift;
                }

                if ($shift) {
                    $shift->schedule_working_hour = getIntervalTime($shift?->clock_in, $shift?->clock_out);
                    $shiftType = 'shift';

                    $companyHoliday = null;
                    if ($schedule?->is_overide_company_holiday == false) {
                        $companyHoliday = $companyHolidays->first(function ($ch) use ($date) {
                            return date('Y-m-d', strtotime($ch->start_at)) <= $date->format("Y-m-d") && date('Y-m-d', strtotime($ch->end_at)) >= $date->format("Y-m-d");
                        });

                        if ($companyHoliday) {
                            $shift = $companyHoliday;
                            $shiftType = 'company_holiday';
                        }
                    }

                    if ($schedule?->is_overide_national_holiday == false && is_null($companyHoliday)) {
                        $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                            return date('Y-m-d', strtotime($nh->start_at)) <= $date->format("Y-m-d") && date('Y-m-d', strtotime($nh->end_at)) >= $date->format("Y-m-d");
                        });

                        if ($nationalHoliday) {
                            $shift = $nationalHoliday;
                            $shiftType = 'national_holiday';
                        }
                    }

                    unset($shift->pivot);

                    $dataAttendance[] = [
                        // 'user' => $user,
                        'date' => $date->format('Y-m-d'),
                        'shift_type' => $shiftType,
                        'shift' => $shift,
                        'attendance' => $attendance
                    ];
                }
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

        if ($export == 'export-2') return Excel::download(new \App\Exports\Attendance\AttendanceHorizontalReport($dateRange, $data), 'attendances.xlsx');
        if ($export) return Excel::download(new AttendanceReport($data), 'attendances.xlsx');

        return DefaultResource::collection($data);
    }

    public function index(IndexRequest $request)
    {
        if (isset($request->filter['user_id'])) {
            $user = User::where('id', $request->filter['user_id'])->firstOrFail(['id', 'company_id']);
        } else {
            $user = auth('sanctum')->user();
        }

        // $timeoffRegulation = TimeoffRegulation::where('company_id', $user->company_id)->first(['id', 'cut_off_date']);
        // $payrollSetting = PayrollSetting::where('company_id', $user->company_id)->first(['id', 'cut_off_date']);

        $month = date('m');

        $year = isset($request->filter['year']) ? $request->filter['year'] : date('Y');
        $month = isset($request->filter['month']) ? $request->filter['month'] : $month;
        // if (date('d') <= $payrollSetting->cut_off_date) {
        //     $month -= 1;
        // }

        // $startDate = date(sprintf('%s-%s-%s', $year, $month, $payrollSetting->cut_off_date));
        // $endDate = date('Y-m-d', strtotime($startDate . '+1 month'));
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $data = [];

        $summaryPresentAbsent = 0;
        $summaryPresentOnTime = 0;
        $summaryPresentLateClockIn = 0;
        $summaryPresentEarlyClockOut = 0;
        $summaryNotPresentAbsent = 0;
        $summaryNotPresentNoClockIn = 0;
        $summaryNotPresentNoClockOut = 0;
        $summaryAwayDayOff = 0;
        $summaryAwayTimeOff = 0;

        // $schedule = ScheduleService::getTodaySchedule($user, $startDate)?->load(['shifts' => fn($q) => $q->orderBy('order')]);
        // $schedule = ScheduleService::getTodaySchedule($user, $endDate, ['id'], ['id']);
        // if ($schedule) {
        // $order = $schedule->shifts->where('id', $schedule->shift->id);
        // $orderKey = array_keys($order->toArray())[0];
        // $totalShifts = $schedule->shifts->count();

        $attendances = Attendance::where('user_id', $user->id)
            ->where(fn($q) => $q->whereHas('details', fn($q) => $q->approved())->orHas('timeoff'))
            ->with([
                'shift' => fn($q) => $q->withTrashed()->selectMinimalist(),
                'timeoff.timeoffPolicy',
                'clockIn' => fn($q) => $q->approved(),
                'clockOut' => fn($q) => $q->approved(),
                'details' => fn($q) => $q->approved()->orderBy('created_at')
            ])
            ->whereDateBetween($startDate, $endDate)
            ->get();

        $companyHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereCompanyHoliday()->get();
        $nationalHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereNationalHoliday()->get();

        foreach ($dateRange as $date) {
            $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name', 'is_overide_national_holiday', 'is_overide_company_holiday', 'effective_date'], ['id', 'is_dayoff', 'name', 'clock_in', 'clock_out']);
            // 1. kalo tgl merah(national holiday), shift nya pake tgl merah
            // 2. kalo company event(holiday), shiftnya pake holiday
            // 3. kalo schedulenya is_overide_national_holiday == false, shiftnya pake shift
            // 4. kalo schedulenya is_overide_company_holiday == false, shiftnya pake shift
            // 5. kalo ngambil timeoff, shfitnya tetap pake shift hari itu, munculin data timeoffnya
            $date = $date->format('Y-m-d');
            $attendance = $attendances->firstWhere('date', $date);
            if ($attendance) {
                $shift = $attendance->shift;

                if ($attendance->clockIn) {
                    $shiftClockInTime = strtotime($shift->clock_in);
                    $clockInTime = strtotime(date('H:i:s', strtotime($attendance->clockIn->time)));

                    // calculate present on time (include early clock in)
                    if ($clockInTime <= $shiftClockInTime) {
                        $summaryPresentOnTime += 1;
                    }

                    // calculate late clock in
                    if ($clockInTime > $shiftClockInTime) {
                        $summaryPresentLateClockIn += 1;
                    }

                    // calculate if no clock out but clock in
                    if (!$attendance->clockOut) {
                        $summaryNotPresentNoClockOut += 1;
                    }
                }

                if ($attendance->clockOut) {
                    $shiftClockOutTime = strtotime($shift->clock_out);
                    $clockOutTime = strtotime(date('H:i:s', strtotime($attendance->clockOut->time)));

                    // calculate early clock out
                    if ($clockOutTime < $shiftClockOutTime) {
                        $summaryPresentEarlyClockOut += 1;
                    }

                    // calculate if no clock in but clock out
                    if (!$attendance->clockIn) {
                        $summaryNotPresentNoClockIn += 1;
                    }
                }

                if ($attendance->clockIn && $attendance->clockOut) {
                    $summaryPresentAbsent += 1;
                }

                // calculate timeoff
                if ($attendance->timeoff) {
                    $summaryAwayTimeOff += 1;
                }

                // load overtime
                $totalOvertime = AttendanceService::getSumOvertimeDuration($user, $date);
                $attendance->total_overtime = $totalOvertime;

                // load task
                // $totalTask = TaskService::getSumDuration($user, $date);
                // $attendance->total_task = $totalTask;
            } else {
                $shift = $schedule?->shift;
                $summaryNotPresentAbsent += 1;
            }
            $shiftType = 'shift';

            $companyHoliday = null;
            if ($schedule?->is_overide_company_holiday == false) {
                $companyHoliday = $companyHolidays->first(function ($ch) use ($date) {
                    return date('Y-m-d', strtotime($ch->start_at)) <= $date && date('Y-m-d', strtotime($ch->end_at)) >= $date;
                });

                if ($companyHoliday) {
                    $shift = $companyHoliday;
                    $shiftType = 'company_holiday';
                }
            }

            if ($schedule?->is_overide_national_holiday == false && is_null($companyHoliday)) {
                $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                    return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
                });

                if ($nationalHoliday) {
                    $shift = $nationalHoliday;
                    $shiftType = 'national_holiday';
                }
            }

            unset($shift->pivot);

            $data[] = [
                'date' => $date,
                'shift_type' => $shiftType,
                'shift' => $shift,
                'attendance' => $attendance
            ];

            // if (($orderKey + 1) === $totalShifts) {
            //     $orderKey = 0;
            // } else {
            //     $orderKey++;
            // }
        }
        // }

        return response()->json([
            'summary' => [
                'present' => [
                    'absent' => $summaryPresentAbsent,
                    'on_time' => $summaryPresentOnTime,
                    'late_clock_in' => $summaryPresentLateClockIn,
                    'early_clock_out' => $summaryPresentEarlyClockOut,
                ],
                'not_present' => [
                    'absent' => $summaryNotPresentAbsent,
                    'no_clock_in' => $summaryNotPresentNoClockIn,
                    'no_clock_out' => $summaryNotPresentNoClockOut,
                ],
                'away' => [
                    'day_off' => $summaryAwayDayOff,
                    'time_off' => $summaryAwayTimeOff,
                ]
            ],
            'data' => $data,
        ]);
    }

    public function employeesSummary(ChildrenRequest $request)
    {
        $user = auth('sanctum')->user();

        $branchId = isset($request['filter']['branch_id']) && !empty($request['filter']['branch_id']) ? $request['filter']['branch_id'] : null;
        $clientId = isset($request['filter']['client_id']) && !empty($request['filter']['client_id']) ? $request['filter']['client_id'] : null;
        $userIds = isset($request['filter']['user_ids']) && !empty($request['filter']['user_ids']) ? explode(',', $request['filter']['user_ids']) : null;

        $query = User::select('id', 'branch_id', 'name', 'nik')
            ->tenanted(true)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->with([
                'branch' => fn($q) => $q->select('id', 'name')
            ]);

        $date = $request->filter['date'];

        if (isset($request->filter['search'])) {
            $query = $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter['search'] . '%');
                $q->orWhere('nik', 'LIKE', '%' . $request->filter['search'] . '%');
            });
        }

        $summaryPresentOnTime = 0;
        $summaryPresentLateClockIn = 0;
        $summaryPresentEarlyClockOut = 0;
        $summaryNotPresentAbsent = 0;
        $summaryNotPresentNoClockIn = 0;
        $summaryNotPresentNoClockOut = 0;
        $summaryAwayDayOff = 0;
        $summaryAwayTimeOff = 0;

        foreach ($query->get() as $user) {
            $schedule = ScheduleService::getTodaySchedule($user, $date, scheduleType: $request->filter['schedule_type'] ?? ScheduleType::ATTENDANCE->value);

            $attendance = $user->attendances()
                ->where('date', $date)
                ->whereHas('schedule', function ($q) {
                    $q->where('schedules.type', $request->filter['schedule_type'] ?? ScheduleType::ATTENDANCE->value);
                })
                ->with([
                    'shift' => fn($q) => $q->withTrashed()->select('id', 'clock_in', 'clock_out'),
                    'timeoff' => fn($q) => $q->select('id'),
                    'clockIn' => fn($q) => $q->select('attendance_id', 'time'),
                    'clockOut' => fn($q) => $q->select('attendance_id', 'time'),
                ])->first();

            if ($attendance) {
                $shift = $attendance->shift;

                if ($attendance->clockIn) {
                    $shiftClockInTime = strtotime($shift->clock_in);
                    $clockInTime = strtotime(date('H:i:s', strtotime($attendance->clockIn->time)));

                    // calculate present on time (include early clock in)
                    if ($clockInTime <= $shiftClockInTime) {
                        $summaryPresentOnTime += 1;
                    }

                    // calculate late clock in
                    if ($clockInTime > $shiftClockInTime) {
                        $summaryPresentLateClockIn += 1;
                    }

                    // calculate if no clock out but clock in
                    if (!$attendance->clockOut) {
                        $summaryNotPresentNoClockOut += 1;
                    }
                }

                if ($attendance->clockOut) {
                    $shiftClockOutTime = strtotime($shift->clock_out);
                    $clockOutTime = strtotime(date('H:i:s', strtotime($attendance->clockOut->time)));

                    // calculate early clock out
                    if ($clockOutTime < $shiftClockOutTime) {
                        $summaryPresentEarlyClockOut += 1;
                    }

                    // calculate if no clock in but clock out
                    if (!$attendance->clockIn) {
                        $summaryNotPresentNoClockIn += 1;
                    }
                }

                // calculate timeoff
                if ($attendance->timeoff) {
                    $summaryAwayTimeOff += 1;
                }
            } else {
                $shift = $schedule?->shift ?? null;
                $summaryNotPresentAbsent += 1;
            }
            // $shiftType = 'shift';

            // $companyHolidayData = null;
            // if ($schedule?->is_overide_company_holiday == false) {
            //     $companyHolidayData = $companyHolidays->first(function ($companyHoliday) use ($date) {
            //         return date('Y-m-d', strtotime($companyHoliday->start_at)) <= $date && date('Y-m-d', strtotime($companyHoliday->end_at)) >= $date;
            //     });

            //     if ($companyHolidayData) {
            //         $shift = $companyHolidayData;
            //         $shiftType = 'company_holiday';
            //     }
            // }

            // if ($schedule?->is_overide_national_holiday == false && is_null($companyHolidayData)) {
            //     $nationalHoliday = $nationalHolidays->firstWhere('date', $date);
            //     if ($nationalHoliday) {
            //         $shift = $nationalHoliday;
            //         $shiftType = 'national_holiday';
            //     }
            // }

            // unset($shift->pivot);

            // $user->date = $date;
            // $user->shift_type = $shiftType;
            // $user->shift = $shift;
            // $user->attendance = $attendance;
        }

        $summary = [
            'present' => [
                'on_time' => $summaryPresentOnTime,
                'late_clock_in' => $summaryPresentLateClockIn,
                'early_clock_out' => $summaryPresentEarlyClockOut,
            ],
            'not_present' => [
                'absent' => $summaryNotPresentAbsent,
                'no_clock_in' => $summaryNotPresentNoClockIn,
                'no_clock_out' => $summaryNotPresentNoClockOut,
            ],
            'away' => [
                'day_off' => $summaryAwayDayOff,
                'time_off' => $summaryAwayTimeOff,
            ]
        ];

        return new DefaultResource($summary);
    }

    public function employees(ChildrenRequest $request)
    {
        $branchId = isset($request['filter']['branch_id']) && !empty($request['filter']['branch_id']) ? $request['filter']['branch_id'] : null;
        $clientId = isset($request['filter']['client_id']) && !empty($request['filter']['client_id']) ? $request['filter']['client_id'] : null;
        $userIds = isset($request['filter']['user_ids']) && !empty($request['filter']['user_ids']) ? explode(',', $request['filter']['user_ids']) : null;

        $query = User::select('id', 'company_id', 'branch_id', 'name', 'nik')
            ->tenanted(true)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->with([
                'branch' => fn($q) => $q->select('id', 'name')
            ]);

        $date = $request->filter['date'];

        $users = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::callback('has_attendance', fn($query, $value) => $query->when($value == 1, fn($q) => $q->whereHas('attendance', fn($q) => $q->whereDate('date', $date)))),
                AllowedFilter::scope('schedule_type'),
                'nik',
                'name',
            ])
            ->allowedSorts([
                'nik',
                'name',
            ])
            ->paginate($this->per_page);

        $users->map(function ($user) use ($date, $request) {
            $companyHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($date, $date)->whereCompanyHoliday()->get();
            $nationalHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($date, $date)->whereNationalHoliday()->get();

            $schedule = ScheduleService::getTodaySchedule($user, $date, scheduleType: $request->filter['schedule_type'] ?? ScheduleType::ATTENDANCE->value);

            $attendance = $user->attendances()
                ->where('date', $date)
                ->where(fn($q) => $q->whereHas('details', fn($q) => $q->approved())->orHas('timeoff'))
                // ->whereHas('details', fn($q) => $q->approved())
                ->with([
                    'shift' => fn($q) => $q->withTrashed()->selectMinimalist(),
                    'timeoff.timeoffPolicy',
                    'clockIn' => fn($q) => $q->approved(),
                    'clockOut' => fn($q) => $q->approved(),
                    // 'details' => fn ($q) => $q->orderBy('created_at')
                ])->first();

            if ($attendance) {
                $shift = $attendance->shift;

                // load overtime
                $totalOvertime = AttendanceService::getSumOvertimeDuration($user, $date);
                $attendance->total_overtime = $totalOvertime;

                // load task
                // $totalTask = TaskService::getSumDuration($user, $date);
                // $attendance->total_task = $totalTask;
            } else {
                $shift = $schedule?->shift ?? null;
            }
            $shiftType = 'shift';

            $companyHoliday = null;
            if ($schedule?->is_overide_company_holiday == false) {
                $companyHoliday = $companyHolidays->first(function ($ch) use ($date) {
                    return date('Y-m-d', strtotime($ch->start_at)) <= $date && date('Y-m-d', strtotime($ch->end_at)) >= $date;
                });

                if ($companyHoliday) {
                    $shift = $companyHoliday;
                    $shiftType = 'company_holiday';
                }
            }

            if ($schedule?->is_overide_national_holiday == false && is_null($companyHoliday)) {
                $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                    return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
                });

                if ($nationalHoliday) {
                    $shift = $nationalHoliday;
                    $shiftType = 'national_holiday';
                }
            }

            unset($shift->pivot);

            $user->date = $date;
            $user->shift_type = $shiftType;
            $user->shift = $shift;
            $user->attendance = $attendance;
        });

        return DefaultResource::collection($users);
    }

    public function logs()
    {
        $attendance = QueryBuilder::for(
            AttendanceDetail::whereHas('attendance', fn($q) => $q->where('user_id', auth()->id()))
                ->with('approvals', fn($q) => $q->with('user', fn($q) => $q->select('id', 'name')))
        )
            ->allowedFilters([
                'is_clock_in',
                'type',
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                // 'approval_status',
                // 'approved_by',
                AllowedFilter::callback('user_id', fn($query, $value) => $query->whereHas('attendance', fn($q) => $q->where('user_id', $value))),
                AllowedFilter::callback('shift_id', fn($query, $value) => $query->whereHas('attendance', fn($q) => $q->where('shift_id', $value))),
            ])
            ->allowedIncludes([
                AllowedInclude::callback('attendance', function ($query) {
                    $query->select('id', 'schedule_id', 'shift_id', 'code')
                        ->with('shift', fn($q) => $q->withTrashed()->selectMinimalist());
                }),
            ])
            ->allowedSorts(['id', 'is_clock_in', 'created_at'])
            ->paginate($this->per_page);

        return DefaultResource::collection($attendance);
    }

    public function show(Attendance $attendance)
    {
        $attendance = QueryBuilder::for(Attendance::where('id', $attendance->id))
            ->allowedIncludes(self::ALLOWED_INCLUDES)
            ->firstOrFail();

        return new AttendanceResource($attendance);
    }

    public function store(StoreRequest $request)
    {
        $user = auth('sanctum')->user();

        if ($request->is_offline_mode) {
            $attendance = AttendanceService::getTodayAttendance(date: $request->time, user: $user, isCheckByDetails: false);
            if ($attendance) {
                $request->merge([
                    'schedule_id' => $attendance->schedule_id,
                    'shift_id' => $attendance->shift_id
                ]);
            } else {
                $schedule = ScheduleService::getTodaySchedule($user, $request->time);
                $request->merge([
                    'schedule_id' => $schedule->id,
                    'shift_id' => $schedule->shift->id
                ]);
            }
        } else {
            $attendance = AttendanceService::getTodayAttendance($request->time, $request->schedule_id, $request->shift_id, $user, false);
        }

        if (config('app.enable_face_rekognition') === true && !$request->is_offline_mode) {
            try {
                $compareFace = Rekognition::compareFace($user, $request->file('file'));
                if (!$compareFace) {
                    return $this->errorResponse(message: 'Face not match!', code: 400);
                }
            } catch (Exception $e) {
                return $this->errorResponse(message: $e->getMessage());
            }
        }

        DB::beginTransaction();
        try {
            if (!$attendance) {
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => date('Y-m-d', strtotime($request->time)),
                    'schedule_id' => $request->schedule_id,
                    'shift_id' => $request->shift_id,
                ]);
            }

            /** @var AttendanceDetail $attendanceDetail */
            $attendanceDetail = $attendance->details()->create($request->validated());

            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $mediaCollection = MediaCollection::ATTENDANCE->value;
                $attendanceDetail->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }

            // moved to AttendanceDetail booted created
            // AttendanceRequested::dispatchIf($attendanceDetail->type->is(AttendanceType::MANUAL), $attendanceDetail);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new AttendanceResource($attendance);
    }

    public function manualAttendance(ManualAttendanceRequest $request)
    {
        /**
         *
         * manual attendance juga kayanya masalahnya hampir sama kaya request attendance
         *
         * SOLUSI sementara
         * manual attendance harus ambil ScheduleService::getTodaySchedule() yang hari ini
         * harus dibahas lagi di fe cara pakenya gimana
         *
         */
        $user = User::find($request->user_id);

        $schedule = ScheduleService::getTodaySchedule($user, $request->date);

        if (!$schedule) {
            return $this->errorResponse(message: 'Schedule not found!', code: 404);
        }

        $attendance = AttendanceService::getTodayAttendance(date: $request->date, user: $user, isCheckByDetails: false);

        $shiftId = $request->shift_id;
        if ($request->is_offline_mode == true) {
            $shiftId = $schedule->shift->id;
        }

        DB::beginTransaction();
        try {
            if (!$attendance) {
                $attendance = $user->attendances()->create([
                    'schedule_id' => $schedule->id,
                    'shift_id' => $shiftId,
                    'date' => $request->date,
                ]);
            } else {
                $attendance->update([
                    'schedule_id' => $schedule->id,
                    'shift_id' => $shiftId,
                ]);
            }

            // clock in
            if ($request->clock_in) {
                $attendance->details()->create([
                    'is_clock_in' => true,
                    'time' => $request->date . ' ' . $request->clock_in,
                    'type' => $request->type,
                    // 'approval_status' => ApprovalStatus::APPROVED,
                    // 'approved_at' => now(),
                    // 'approved_by' => auth('sanctum')->id(),
                ]);
            }

            // clock out
            if ($request->clock_out) {
                $attendance->details()->create([
                    'is_clock_in' => false,
                    'time' => $request->date . ' ' . $request->clock_out,
                    'type' => $request->type,
                    // 'approval_status' => ApprovalStatus::APPROVED,
                    // 'approved_at' => now(),
                    // 'approved_by' => auth('sanctum')->id(),
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new AttendanceResource($attendance->load('details'));
    }

    public function update(StoreRequest $request)
    {
        $user = auth('sanctum')->user();
        $attendance = AttendanceService::getTodayAttendance($request->time, $request->schedule_id, $request->shift_id, $user);

        if (config('app.enable_face_rekognition') === true) {
            try {
                $compareFace = Rekognition::compareFace($user, $request->file('file'));
                if (!$compareFace) {
                    return $this->errorResponse(message: 'Face not match!', code: 400);
                }
            } catch (Exception $e) {
                return $this->errorResponse(message: $e->getMessage());
            }
        }

        DB::beginTransaction();
        try {
            if (!$attendance) {
                $data = [
                    'user_id' => $user->id,
                    'date' => date('Y-m-d', strtotime($request->time)),
                    ...$request->validated(),
                ];
                $attendance = Attendance::create($data);
            }

            /** @var AttendanceDetail $attendanceDetail */
            $attendanceDetail = $attendance->details()->create($request->validated());

            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $mediaCollection = MediaCollection::ATTENDANCE->value;
                $attendanceDetail->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }

            AttendanceRequested::dispatchIf($attendanceDetail->type->is(AttendanceType::MANUAL), $attendanceDetail);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
            return $this->errorResponse($e->getMessage());
        }

        return new AttendanceResource($attendance);
    }

    public function request(RequestAttendanceRequest $request)
    {
        /**
         * request attendance masih harus dibahas lagi. soalnya $request->schedule_id & $request->shfit_id masih pake schedule today
         * atau schedule hari ini saat user request.
         *
         * sedangkan jika dia request di tanggal yang dipilih, lalu schedule_id atau shift_id (di table attendances) nya beda,
         * nanti sistem akan merecord attendance baru
         *
         * kayanya ini yang sering kejadian attendance di tanggal yang sama ada data yang dobel. harusnya satu tanggal satu data
         * attendance.
         *
         * solusi sementara (tapi belum di implementasi).
         * harusnya AttendanceService::getTodayAttendance() parameternya date aja, gaperlu kirim schedule_id & shift_id, karena
         * masalah nya ada disitu. kalo schedule_id atau shift_id nya beda, maka akan membuat attendance baru.
         *
         */

        // pemeriksaan kehadiran hri ini
        $attendance = AttendanceService::getTodayAttendance($request->date, $request->schedule_id, $request->shift_id, auth('sanctum')->user(), false);

        DB::beginTransaction();
        try {
            // membuat data kehadiran baru jika tidak ada
            if (!$attendance) {
                $attendance = Attendance::create($request->validated());
            }

            if ($request->is_clock_in) {
                $attendance->details()->create([
                    'is_clock_in' => true,
                    'time' => $request->date . ' ' . $request->clock_in_hour,
                    'type' => $request->type,
                    'note' => $request->note,
                ]);
                // AttendanceRequested::dispatchIf($attendanceDetailClockIn->type->is(AttendanceType::MANUAL), $attendanceDetailClockIn);
            }

            if ($request->is_clock_out) {
                $attendance->details()->create([
                    'is_clock_in' => false,
                    'time' => $request->date . ' ' . $request->clock_out_hour,
                    'type' => $request->type,
                    'note' => $request->note,
                ]);
                // AttendanceRequested::dispatchIf($attendanceDetailClockOut->type->is(AttendanceType::MANUAL), $attendanceDetailClockOut);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->errorResponse($e->getMessage());
        }

        return new AttendanceResource($attendance);
    }


    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $attendance = Attendance::withTrashed()->findOrFail($id);
        $attendance->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $attendance = Attendance::withTrashed()->findOrFail($id);
        $attendance->restore();

        return new AttendanceResource($attendance);
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = AttendanceDetail::myApprovals()
            ->whereApprovalStatus($request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = AttendanceDetail::where('type', AttendanceType::MANUAL)
            ->myApprovals()
            ->with('attendance', fn($q) => $q->with([
                'user' => fn($q) => $q->select('id', 'name'),
                'shift' => fn($q) => $q->withTrashed()->selectMinimalist(),
                'schedule' => fn($q) => $q->select('id', 'name')
            ]));

        $attendances = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                'created_at',
            ])
            ->allowedSorts([
                'id',
                'created_at',
            ])
            ->paginate($this->per_page);

        return AttendanceApprovalsResource::collection($attendances);
    }

    public function showApproval(AttendanceDetail $attendanceDetail)
    {
        $attendanceDetail->load(
            [
                // 'attendance' => fn($q) => $q->select('id', 'user_id', 'shift_id', 'schedule_id')
                'attendance' => fn($q) => $q
                    ->with([
                        'user' => fn($q) => $q->select('id', 'name'),
                        'shift' => fn($q) => $q->withTrashed()->selectMinimalist(),
                        'schedule' => fn($q) => $q->select('id', 'name')
                    ])
            ]
        );

        return new AttendanceDetailResource($attendanceDetail);
    }

    public function approveValidate(int $id, ?int $approverId = null)
    {
        $attendanceDetail = AttendanceDetail::findOrFail($id);
        $requestApproval = $attendanceDetail->approvals()->where('user_id', $approverId ?? auth()->id())->first();

        if (!$requestApproval) {
            throw new NotFoundHttpException('You are not registered as approved');
        }

        if (!$attendanceDetail->isDescendantApproved()) {
            throw new UnprocessableEntityHttpException('You have to wait for your subordinates to approve');
        }

        if ($attendanceDetail->approval_status == ApprovalStatus::APPROVED->value || $requestApproval->approval_status->in([ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])) {
            throw new UnprocessableEntityHttpException('Status can not be changed');
        }

        return $requestApproval;
    }

    public function bulkApprove(BulkApproveRequest $request)
    {
        $approverId = auth('sanctum')->id();
        $requestApprovals = collect($request->ids)->map(fn($id) => $this->approveValidate($id, $approverId));

        $data = $request->only(['approval_status', 'approved_by', 'approved_at']);
        DB::beginTransaction();
        try {
            $requestApprovals->each(fn($requestApproval) => $requestApproval->update($data));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->updatedResponse("Data " . $request->approval_status . " successfully");
    }

    public function approve(NewApproveRequest $request, int $id)
    {
        $requestApproval = $this->approveValidate($id);

        DB::beginTransaction();
        try {
            $requestApproval->update($request->validated());
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->updatedResponse("Data " . $request->approval_status . " successfully");
    }

    public function clear()
    {
        $attendanceDetails = AttendanceDetail::where('type', AttendanceType::AUTOMATIC)
            ->has('approvals')
            ->get(['id']);

        foreach ($attendanceDetails as $attendanceDetail) {
            $attendanceDetail->approvals()->delete();
            DatabaseNotification::where('type', 'App\\Notifications\\Attendance\\RequestAttendance')
                ->where('data->type', 'request_attendance')
                ->where('data->model_id', $attendanceDetail->id)
                ->delete();
        }
        die('dono');
    }
}
