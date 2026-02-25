<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Event;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Enums\ScheduleType;
use Illuminate\Http\Request;
use App\Enums\ApprovalStatus;
use App\Enums\AttendanceType;
use App\Services\TaskService;
use App\Enums\MediaCollection;
use App\Models\AttendanceDetail;
use App\Exports\AttendanceReport;
use App\Imports\AttendanceImport;
use App\Services\Aws\Rekognition;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\AttendanceService;
use App\Models\DatabaseNotification;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\DefaultResource;
use Spatie\QueryBuilder\AllowedInclude;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Api\NewApproveRequest;
use App\Http\Requests\Api\BulkApproveRequest;
use App\Events\Attendance\AttendanceRequested;
use App\Http\Requests\Api\Attendance\IndexRequest;
use App\Http\Requests\Api\Attendance\StoreRequest;
use App\Http\Requests\Api\Attendance\ChildrenRequest;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Http\Requests\Api\Attendance\ExportReportRequest;
use App\Http\Resources\Attendance\AttendanceDetailResource;
use App\Http\Requests\Api\Attendance\ManualAttendanceRequest;
use App\Http\Requests\Api\Attendance\RequestAttendanceRequest;
use App\Http\Resources\Attendance\AttendanceApprovalsResource;
use App\Models\OvertimeRequest;
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

        $isShowResignUsers = isset($request['filter']['is_show_resign_users']) && !empty($request['filter']['is_show_resign_users']) ? $request['filter']['is_show_resign_users'] : null;
        $branchId = isset($request['filter']['branch_id']) && !empty($request['filter']['branch_id']) ? $request['filter']['branch_id'] : null;
        $userIds = isset($request['filter']['user_ids']) && !empty($request['filter']['user_ids']) ? explode(',', $request['filter']['user_ids']) : null;

        $users = User::tenanted(true)
            // ->where('join_date', '<=', $startDate)
            ->where(fn($q) => $q->whereNull('resign_date')->orWhere('resign_date', '>=', $endDate))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->when($isShowResignUsers, fn($q) => $q->showResignUsers($isShowResignUsers))
            ->get(['id', 'company_id', 'name', 'nik']);

        $data = [];
        foreach ($users as $user) {
            $companyHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereCompanyHoliday()->get();
            $nationalHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereNationalHoliday()->get();

            $user->setAppends([]);

            $attendances = AttendanceService::getUserAttendancesInPeriod(
                $user,
                $startDate,
                $endDate,
                [
                    'shift' => fn($q) => $q->withTrashed()->selectMinimalist(['is_enable_grace_period', 'clock_in_dispensation', 'clock_out_dispensation', 'time_dispensation'])
                ],
                ['id', 'user_id', 'schedule_id', 'shift_id', 'timeoff_id', 'event_id', 'code', 'date']
            );

            $dataAttendance = [];
            foreach ($dateRange as $date) {
                $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name', 'is_overide_national_holiday', 'is_overide_company_holiday'], ['id', 'name', 'is_dayoff', 'clock_in', 'clock_out']);

                // if (!$schedule || !$schedule->shift) {
                //     continue;
                // }

                $attendance = $attendances->firstWhere('date', $date->format('Y-m-d'));

                if ($attendance) {
                    $shift = $attendance->shift;

                    // if ($attendance->clockIn) {
                    //     $attendance->clock_in = $attendance->clockIn;
                    // }
                    // if ($attendance->clockOut) {
                    //     $attendance->clock_out = $attendance->clockOut;
                    // }
                    // if ($attendance->timeoff) {
                    //     $attendance->timeoff = $attendance->timeoff;
                    //     if ($attendance->timeoff->timeoffPolicy) {
                    //         $attendance->timeoff->timeoffPolicy = $attendance->timeoff->timeoffPolicy;
                    //     }
                    // }

                    // load overtime
                    $overtimeDurationBeforeShift = AttendanceService::getSumOvertimeDuration(user: $user, startDate: $date, formatText: false, query: fn($q) => $q->where('is_after_shift', false));
                    $attendance->overtime_duration_before_shift = $overtimeDurationBeforeShift;

                    $overtimeDurationAfterShift = AttendanceService::getSumOvertimeDuration(user: $user, startDate: $date, formatText: false, query: fn($q) => $q->where('is_after_shift', true));
                    $attendance->overtime_duration_after_shift = $overtimeDurationAfterShift;

                    // load task
                    $totalTask = TaskService::getSumDuration($user, $date);
                    $attendance->total_task = $totalTask;

                    $remainingTime = 0;
                    $attendance->late_in = "00:00:00";
                    if ($attendance->clockIn && !$attendance->shift->is_dayoff) {
                        list($diffInMinute, $diffInTime, $remainingTime) = AttendanceService::getTotalLateTime($attendance->clockIn, $shift);
                        $attendance->late_in = $diffInTime;
                    }

                    $attendance->early_out = "00:00:00";
                    if ($attendance->clockOut && !$attendance->shift->is_dayoff) {
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
            $user = User::where('id', $request->filter['user_id'])->with('payrollInfo', fn($q) => $q->select('user_id', 'is_ignore_alpa'))->firstOrFail(['id', 'company_id']);
        } else {
            $user = auth('sanctum')->user();
        }

        $filterStartDate = $request->filter['start_date'] ?? null;
        $filterEndDate   = $request->filter['end_date'] ?? null;
        $filterMonth = !empty($request->filter['month']) ? $request->filter['month'] : null;
        $filterYear = !empty($request->filter['year']) ? $request->filter['year'] : null;
        if ($filterMonth) {
            $filterYear = $filterYear ?? date('Y');
            $startDate = Carbon::create($filterYear, $filterMonth, 1);
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($filterYear && !$filterMonth) {
            $filterMonth = $filterMonth ?? date('m');
            $startDate = Carbon::create($filterYear, $filterMonth, 1);
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($filterStartDate && $filterEndDate) {
            $startDate = Carbon::createFromFormat('Y-m-d', $filterStartDate);
            $endDate = Carbon::createFromFormat('Y-m-d', $filterEndDate);
        } else {
            $startDate = now()->startOfMonth();
            $endDate   = now()->endOfMonth();
        }

        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $data = [];

        $summaryPresentAbsent = 0;
        $summaryPresentOnTime = 0;
        $summaryPresentLateClockIn = 0;
        $summaryPresentEarlyClockOut = 0;
        $summaryNotPresentAbsent = 0;
        $summaryNotPresentNoClockIn = 0;
        $summaryNotPresentNoClockOut = 0;
        $summaryAwayTimeOff = 0;

        $attendances = AttendanceService::getUserAttendancesInPeriod($user, $startDate, $endDate, ['details' => fn($q) => $q->approved()->orderBy('created_at')]);

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

            $shiftType = 'shift';

            $companyHoliday = null;
            $isHoliday = false;
            if ($schedule?->is_overide_company_holiday == false) {
                $companyHoliday = $companyHolidays->first(function ($ch) use ($date) {
                    return date('Y-m-d', strtotime($ch->start_at)) <= $date && date('Y-m-d', strtotime($ch->end_at)) >= $date;
                });

                if ($companyHoliday) {
                    $isHoliday = true;
                    $shift = $companyHoliday;
                    $shiftType = 'company_holiday';
                }
            }

            if ($schedule?->is_overide_national_holiday == false && is_null($companyHoliday)) {
                $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                    return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
                });

                if ($nationalHoliday) {
                    $isHoliday = true;
                    $shift = $nationalHoliday;
                    $shiftType = 'national_holiday';
                }
            }

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
                if ($attendance->timeoff && $attendance->timeoff->request_type->is(\App\Enums\TimeoffRequestType::FULL_DAY)) {
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

                if (
                    $user->payrollInfo?->is_ignore_alpa == false && !$shift?->is_dayoff && !$isHoliday
                ) {
                    $summaryNotPresentAbsent += 1;
                }
            }

            unset($shift->pivot);

            $data[] = [
                'date' => $date,
                'shift_type' => $shiftType,
                'shift' => $shift,
                'attendance' => $attendance
            ];
        }

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
                    'time_off' => $summaryAwayTimeOff,
                ]
            ],
            'data' => $data,
        ]);
    }

    public function employeesSummary(ChildrenRequest $request)
    {
        $user = auth('sanctum')->user();

        $isShowResignUsers = isset($request['filter']['is_show_resign_users']) ? $request['filter']['is_show_resign_users'] : null;
        $branchId = isset($request['filter']['branch_id']) && !empty($request['filter']['branch_id']) ? $request['filter']['branch_id'] : null;
        $userIds = isset($request['filter']['user_ids']) && !empty($request['filter']['user_ids']) ? explode(',', $request['filter']['user_ids']) : null;

        $query = User::select('id', 'company_id', 'branch_id', 'name', 'nik')
            ->tenanted(true)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->when(isset($isShowResignUsers), fn($q) => $q->showResignUsers($isShowResignUsers))
            ->with([
                'branch' => fn($q) => $q->select('id', 'name'),
                'payrollInfo' => fn($q) => $q->select('user_id', 'is_ignore_alpa'),
            ]);

        $date = $request->filter['date'];

        if (isset($request->filter['search'])) {
            $query = $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter['search'] . '%');
                $q->orWhere('nik', 'LIKE', '%' . $request->filter['search'] . '%');
            });
        }

        $companyHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($date, $date)->whereCompanyHoliday()->get();
        $nationalHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($date, $date)->whereNationalHoliday()->get();

        $summaryPresentOnTime = 0;
        $summaryPresentLateClockIn = 0;
        $summaryPresentEarlyClockOut = 0;
        $summaryNotPresentAbsent = 0;
        $summaryNotPresentNoClockIn = 0;
        $summaryNotPresentNoClockOut = 0;
        $summaryAwayTimeOff = 0;

        foreach ($query->get() as $user) {
            $schedule = ScheduleService::getTodaySchedule($user, $date, scheduleType: $request->filter['schedule_type'] ?? ScheduleType::ATTENDANCE->value);

            $attendance = $user->attendances()
                ->where('date', $date)
                ->with([
                    'shift' => fn($q) => $q->withTrashed()->select('id', 'clock_in', 'clock_out'),
                    'timeoff' => fn($q) => $q->approved()->select('id', 'request_type'),
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
                if ($attendance->timeoff && $attendance->timeoff->request_type->is(\App\Enums\TimeoffRequestType::FULL_DAY)) {
                    $summaryAwayTimeOff += 1;
                }
            } else {
                // dump('test 1');
                $shift = $schedule?->shift ?? null;
                $companyHoliday = null;
                $isHoliday = false;
                //   dump($user->payrollInfo?->is_ignore_alpa, $shift?->is_dayoff, $isHoliday);
                if ($schedule?->is_overide_company_holiday == false) {
                    $companyHoliday = $companyHolidays->first(function ($ch) use ($date) {
                        return date('Y-m-d', strtotime($ch->start_at)) <= $date && date('Y-m-d', strtotime($ch->end_at)) >= $date;
                    });

                    if ($companyHoliday) {
                        $isHoliday = true;
                    }
                }

                if ($schedule?->is_overide_national_holiday == false && !$isHoliday) {
                    $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                        return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
                    });

                    if ($nationalHoliday) {
                        $isHoliday = true;
                    }
                }

                if (
                    $user->payrollInfo?->is_ignore_alpa === false && !$shift?->is_dayoff && !$isHoliday
                ) {
                    $summaryNotPresentAbsent += 1;
                }
            }
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
                'time_off' => $summaryAwayTimeOff,
            ]
        ];

        return new DefaultResource($summary);
    }

    public function employees(ChildrenRequest $request)
    {
        //default resignUser = false
        $isShowResignUsers = isset($request['filter']['is_show_resign_users']) ? $request['filter']['is_show_resign_users'] : false;
        $branchId = isset($request['filter']['branch_id']) && !empty($request['filter']['branch_id']) ? $request['filter']['branch_id'] : null;
        $userIds = isset($request['filter']['user_ids']) && !empty($request['filter']['user_ids']) ? explode(',', $request['filter']['user_ids']) : null;
        $companyId = isset($request['filter']['company_id']) && !empty($request['filter']['company_id']) ? $request['filter']['company_id'] : null;
        $query = User::select('id', 'company_id', 'branch_id', 'name', 'nik')
            ->tenanted(true)
            ->when(isset($isShowResignUsers), fn($q) => $q->showResignUsers($isShowResignUsers))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->with([
                'branch' => fn($q) => $q->select('id', 'name')
            ]);
        $date = $request->filter['date'];

        // $companyHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($date, $date)->whereCompanyHoliday()->get();
        // $nationalHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($date, $date)->whereNationalHoliday()->get();

        $users = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::callback('has_attendance', fn($query, $value) => $query->when($value == 1, fn($q) => $q->whereHas('attendance', fn($q) => $q->whereDate('date', $date)))),
                AllowedFilter::scope('schedule_type'),
                AllowedFilter::exact('company_id'),
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
                ->with([
                    'shift' => fn($q) => $q->withTrashed()->selectMinimalist(),
                    'timeoff' => fn($q) => $q->approved()->with('timeoffPolicy', fn($q) => $q->select('id', 'type', 'name', 'code')),
                    'clockIn' => fn($q) => $q->approved(),
                    'clockOut' => fn($q) => $q->approved(),
                ])->first();

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

        if (AttendanceService::inLockAttendance($request->time, $user)) {
            throw new UnprocessableEntityHttpException('Attendance is locked');
        }

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
        $attendanceDetail = null;
        DB::transaction(function () use ($request, $user, &$attendance, &$attendanceDetail) {
            if (!$attendance) {
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => date('Y-m-d', strtotime($request->time)),
                    'schedule_id' => $request->schedule_id,
                    'shift_id' => $request->shift_id
                ]);
            }

            $attendanceDetail = $attendance->details()->create($request->validated());

            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $mediaCollection = MediaCollection::ATTENDANCE->value;
                $attendanceDetail->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }
        });

        $overtimeMessage = null;
        try {
            $end = Carbon::parse($attendanceDetail->time);
            $start = Carbon::parse($attendance->shift->clock_out);
            $diff = $end->diffInMinutes($start);

            $defaultOvertime = $user->overtimes()->wherePivot('is_default', true)->first();
            $defaultFlag = (bool) ($defaultOvertime?->pivot?->is_default ?? false);

            $diffMinutes = $end->diffInMinutes($start, false);
            $hours = intdiv(abs($diffMinutes), 60);
            $mins = abs($diffMinutes) % 60;
            $durationStr = sprintf('%02d:%02d:00', $hours, $mins);

            if ($attendanceDetail->is_clock_in == false && $defaultFlag == true) {
                $dataDefaultOvertime = $defaultOvertime->toArray();
                $overtimeMinute = $dataDefaultOvertime['min_auto_overtime_minute'] ?? 0;

                if ($diff >= $overtimeMinute) {
                    $dateOvertime = Carbon::parse($attendanceDetail->time)->toDateString();

                    if (OvertimeRequest::hasPendingOnDate($user->id, $dateOvertime)) {
                        // return $this->errorResponse(message: 'Silahkan buat ulang overtime request', code: 422);
                        $overtimeMessage = "Attendance saved; you have pending overtime request!";
                    } else {
                        OvertimeRequest::create([
                            'overtime_id'   => $dataDefaultOvertime['pivot']['overtime_id'],
                            'user_id'       => $user->id,
                            'schedule_id'   => $request->schedule_id,
                            'shift_id'      => $request->shift_id,
                            'is_after_shift' => true,
                            'date'          => $dateOvertime,
                            'duration'      => $durationStr,
                            'real_duration' => $durationStr,
                            'note'          => 'AUTOMATED FROM SYSTEM',
                        ]);
                        $overtimeMessage = "Attendance and overtime request saved!";
                    }
                } else {
                    $overtimeMessage = "Attendance saved; overtime below minimum treshold";
                }
            } elseif ($attendanceDetail->is_clock_in == false) {
                $overtimeMessage = "Attendance saved; no default overtime configured";
            }
        } catch (Exception $e) {
            report($e);
            $overtimeMessage = "Attendance saved; overtime request failed: " . $e->getMessage();
        }
        $resource = new AttendanceResource($attendance);
        if ($overtimeMessage) {
            return $resource->additional(['message' => $overtimeMessage]);
        }
        return $resource;

        // DB::beginTransaction();
        // try {
        //     if (!$attendance) {
        //         $attendance = Attendance::create([
        //             'user_id' => $user->id,
        //             'date' => date('Y-m-d', strtotime($request->time)),
        //             'schedule_id' => $request->schedule_id,
        //             'shift_id' => $request->shift_id,
        //         ]);
        //     }

        //     /** @var AttendanceDetail $attendanceDetail */
        //     $attendanceDetail = $attendance->details()->create($request->validated());

        //     if ($request->hasFile('file') && $request->file('file')->isValid()) {
        //         $mediaCollection = MediaCollection::ATTENDANCE->value;
        //         $attendanceDetail->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
        //     }

        //     $end = Carbon::parse($attendanceDetail->time); //ambil dari tanggal dan jam request yang dikirim
        //     $start = Carbon::parse($attendance->shift->clock_out); //otomatis ambil tanggal hari ini karena baru di create

        //     $diff = $end->diffInMinutes($start);
        //     $defaultOvertime = $user->overtimes()->wherePivot('is_default', true)->first();
        //     $defaultFlag = (bool) ($defaultOvertime?->pivot?->is_default ?? false);

        //     $diffMinutes = $end->diffInMinutes($start, false); // pakai false jika butuh signed
        //     $hours = intdiv(abs($diffMinutes), 60);
        //     $mins  = abs($diffMinutes) % 60;
        //     $durationStr = sprintf('%02d:%02d:00', $hours, $mins);
        //     $hoursDecimal = OvertimeService::calculateOvertimeDuration($durationStr);
        //     //case 1
        //     if ($attendanceDetail->is_clock_in == false && $defaultFlag == true) {
        //         $dataDefaultOvertime = $defaultOvertime->toArray();
        //         $overtimeMinute = $dataDefaultOvertime["min_auto_overtime_minute"];
        //         if ($diff >= $overtimeMinute) {
        //             $dateOvertime = Carbon::parse($attendanceDetail->time)->toDateString();
        //             if(OvertimeRequest::hasPendingOnDate($user->id, $dateOvertime)){
        //                 return $this->errorResponse(message: 'Silahkan buat ulang overtime request', code: 422);
        //             }

        //             OvertimeRequest::create([
        //                 'overtime_id' => $dataDefaultOvertime["pivot"]["overtime_id"],
        //                 'user_id' => $user->id,
        //                 'schedule_id' => $request->schedule_id,
        //                 'shift_id' => $request->shift_id,
        //                 'is_after_shift' => true,
        //                 'date' => $dateOvertime,
        //                 'duration' => $durationStr,
        //                 'real_duration' => $durationStr,
        //                 'note' => 'AUTOMATED FROM SYSTEM'
        //             ]);
        //         }
        //     }


        //     // moved to AttendanceDetail booted created
        //     // AttendanceRequested::dispatchIf($attendanceDetail->type->is(AttendanceType::MANUAL), $attendanceDetail);
        //     DB::commit();
        // } catch (Exception $e) {
        //     DB::rollBack();
        //     return $this->errorResponse($e->getMessage());
        // }

        // return new AttendanceResource($attendance);
    }

    // public function store(StoreRequest $request)
    // {
    //     try {
    //         $user = auth('sanctum')->user();
    //         // dd($request->validated());
    //         $attendance = $this->attendanceService->storeAttendance($request->validated(),$user);
    //         return new AttendanceResource($attendance);
    //     } catch (\DomainException $e) {
    //         return $this->errorResponse($e->getMessage());
    //     }catch(Exception $e){
    //         return $this->errorResponse($e->getMessage());
    //     }
    // }

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

        if (AttendanceService::inLockAttendance($request->date, $user)) {
            throw new UnprocessableEntityHttpException('Attendance is locked');
        }

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
                    'lat' => $request->lat ?? null,
                    'lng' => $request->lng ?? null,
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
                    'lat' => $request->lat ?? null,
                    'lng' => $request->lng ?? null,
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

        if (AttendanceService::inLockAttendance($request->time, $user)) {
            throw new UnprocessableEntityHttpException('Attendance is locked');
        }

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
        $user = auth('sanctum')->user();
        if ($request->user_id) {
            $user = User::select('id', 'company_id')->where('id', $request->user_id)->firstOrFail();
        }

        if (AttendanceService::inLockAttendance($request->date, $user ?? null)) {
            throw new UnprocessableEntityHttpException('Attendance is locked');
        }

        // pemeriksaan kehadiran hri ini
        $attendance = AttendanceService::getTodayAttendance($request->date, $request->schedule_id, $request->shift_id, auth('sanctum')->user(), false);
        //request overtime
        $defaultOvertime = $user->overtimes()->wherePivot('is_default', true)->first();
        $defaultFlag = (bool) ($defaultOvertime?->pivot?->is_default ?? false);

        $attendanceDetailClockOut = null;
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
                    'lat' => $request->lat ?? null,
                    'lng' => $request->lng ?? null,
                ]);
                // AttendanceRequested::dispatchIf($attendanceDetailClockIn->type->is(AttendanceType::MANUAL), $attendanceDetailClockIn);
            }

            if ($request->is_clock_out) {
                $attendanceDetailClockOut = $attendance->details()->create([
                    'is_clock_in' => false,
                    'time' => $request->date . ' ' . $request->clock_out_hour,
                    'type' => $request->type,
                    'note' => $request->note,
                    'lat' => $request->lat ?? null,
                    'lng' => $request->lng ?? null,
                ]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->errorResponse($e->getMessage());
        }

        $overtimeMessage = null;
        try {
            if ($attendanceDetailClockOut) {

                $end = Carbon::parse($request->clock_out_hour);
                $start = Carbon::parse($attendance->shift->clock_out);
                $diff = $end->diffInMinutes($start);

                $diffMinutesSigned = $end->diffInMinutes($start, false);
                $hours = intdiv(abs($diffMinutesSigned), 60);
                $mins  = abs($diffMinutesSigned) % 60;
                $durationStr = sprintf('%02d:%02d:00', $hours, $mins);
                if ($defaultFlag) {
                    $dataDefaultOvertime = $defaultOvertime->toArray();
                    if ($diff >= $dataDefaultOvertime["min_auto_overtime_minute"]) {
                        if (OvertimeRequest::hasPendingOnDate($user->id, $request->date)) {
                            $overtimeMessage = "Attendance saved; you have pending overtime request.";
                        } else {
                            OvertimeRequest::create([
                                'overtime_id' => $dataDefaultOvertime["pivot"]["overtime_id"],
                                'user_id' => $user->id,
                                'schedule_id' => $request->schedule_id,
                                'shift_id' => $request->shift_id,
                                'is_after_shift' => true,
                                'date' => $request->date,
                                'duration' => $durationStr,
                                'real_duration' => $durationStr,
                                'note' => 'AUTOMATED FROM SYSTEM'
                            ]);
                            $overtimeMessage = "Attendance and overtime request saved!";
                        }
                    } else {
                        $overtimeMessage = "Attendance saved overtime below minimum treshold";
                    }
                } else {
                    $overtimeMessage = "Attendance saved; no default overtime configured";
                }
            }
        } catch (Exception $e) {
            report($e);
            $overtimeMessage = "Attendance saved; overtime request failed: " . $e->getMessage();
        }
        $resource = new AttendanceResource($attendance);
        return $overtimeMessage ? $resource->additional(['message' => $overtimeMessage]) : $resource;
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
            ->whereApprovalStatus($request->filter['approval_status'])
            ->when($request->branch_id, fn($q) => $q->whereBranch($request->branch_id))
            ->when($request->name, fn($q) => $q->whereUserName($request->name))
            ->when($request->created_at, fn($q) => $q->createdAt($request->created_at))
            ->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = AttendanceDetail::where('type', AttendanceType::MANUAL)
            ->myApprovals()
            ->with([
                'attendance' => fn($q) => $q->with([
                    'user' => fn($q) => $q->select('id', 'name'),
                    'shift' => fn($q) => $q->withTrashed()->selectMinimalist(),
                    'schedule' => fn($q) => $q->select('id', 'name'),
                ]),
                'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))
            ]);

        $attendances = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                AllowedFilter::scope('branch_id', 'whereBranch'),
                AllowedFilter::scope('name', 'whereUserName'),
                'created_at',
            ])
            ->allowedSorts([
                'id',
                'created_at',
            ])
            ->paginate($this->per_page);

        return AttendanceApprovalsResource::collection($attendances);
    }

    // public function approvals(Request $request)
    // {
    //     $attendances = $this->attendanceService->getApprovals(
    //         [],
    //         $this->per_page
    //     );
    //     return AttendanceApprovalsResource::collection($attendances);
    // }

    public function showApproval(AttendanceDetail $attendanceDetail)
    {
        $attendanceDetail->load(
            [
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

    // public function showApproval(AttendanceDetail $attendanceDetail)
    // {
    //     $detail = $this->attendanceService->getApprovalDetail($attendanceDetail);

    //     return new AttendanceDetailResource($detail);
    // }

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

    public function importExcel(Request $request): JsonResponse
    {
        // ═══════════════════════════════════════════════════════════
        // STEP 1: Validate Request
        // ═══════════════════════════════════════════════════════════
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240', // Max 10MB
            ],
        ], [
            'file.required' => 'File Excel wajib diupload',
            'file.mimes' => 'File harus berformat Excel (.xlsx, .xls, atau .csv)',
            'file.max' => 'Ukuran file maksimal 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ═══════════════════════════════════════════════════════════
        // STEP 2: Initialize Import Class
        // ═══════════════════════════════════════════════════════════
        try {
            $import = new AttendanceImport();

            // ═══════════════════════════════════════════════════════════
            // STEP 3: Execute Import
            // ═══════════════════════════════════════════════════════════
            Excel::import($import, $request->file('file'));

            // ═══════════════════════════════════════════════════════════
            // STEP 4: Get Import Statistics & normalize (filter summary rows)
            // ═══════════════════════════════════════════════════════════
            $stats = $this->normalizeImportStats($import->getStats());

            // ═══════════════════════════════════════════════════════════
            // STEP 5: Return Response
            // ═══════════════════════════════════════════════════════════
            $hasErrors = count($stats['errors']) > 0;

            return response()->json([
                'success' => !$hasErrors || $stats['created'] > 0 || $stats['updated'] > 0,
                'message' => $this->generateImportMessage($stats),
                'data' => [
                    'total_rows' => $stats['total'],
                    'created' => $stats['created'],
                    'updated' => $stats['updated'],
                    'skipped' => $stats['skipped'],
                    'skipped_summary' => $stats['skipped_summary'] ?? 0,
                    'skipped_invalid' => $stats['skipped_invalid'] ?? $stats['skipped'],
                    'errors' => $stats['errors'],
                ],
            ], $hasErrors && $stats['created'] === 0 && $stats['updated'] === 0 ? 422 : 200);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Handle Excel validation errors
            $failures = $e->failures();
            $errors = [];

            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                ];
            }

            return response()->json([
                'success' => false,
                'message' => 'Validasi data Excel gagal',
                'errors' => $errors,
            ], 422);
        } catch (\Exception $e) {
            // Handle general errors
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat import data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate user-friendly import message
     *
     * @param array $stats
     * @return string
     */
    protected function generateImportMessage(array $stats): string
    {
        $messages = [];

        if ($stats['created'] > 0) {
            $messages[] = "{$stats['created']} data attendance berhasil dibuat";
        }

        if ($stats['updated'] > 0) {
            $messages[] = "{$stats['updated']} data attendance berhasil diupdate";
        }

        $skippedSummary = $stats['skipped_summary'] ?? 0;
        $skippedInvalid = $stats['skipped_invalid'] ?? ($stats['skipped'] ?? 0);

        if ($skippedInvalid > 0) {
            $messages[] = "$skippedInvalid data tidak valid dilewati";
        }

        if ($skippedSummary > 0) {
            $messages[] = "$skippedSummary baris ringkasan (TOTAL FOR EMPLOYEE) dilewati";
        }

        if (empty($messages)) {
            return 'Tidak ada data yang diproses';
        }

        $message = implode(', ', $messages);

        if (count($stats['errors']) > 0) {
            $message .= '. Silakan cek detail error untuk informasi lengkap.';
        }

        return ucfirst($message);
    }

    /**
     * Normalize import stats by filtering out summary rows (e.g., "TOTAL FOR EMPLOYEE : ...").
     * We don't change importer logic; only adjust the response payload here.
     */
    protected function normalizeImportStats(array $stats): array
    {
        $errors = $stats['errors'] ?? [];
        $filtered = [];
        $summaryCount = 0;

        foreach ($errors as $err) {
            // values['nik'] when coming from Excel validator; fallback to top-level 'nik'
            $nik = $err['values']['nik'] ?? $err['nik'] ?? null;
            $isSummary = false;
            if (is_string($nik)) {
                $val = trim($nik);
                if (preg_match('/^TOTAL\s+FOR\s+EMPLOYEE\s*:/i', $val)) {
                    $isSummary = true;
                }
            }

            // As a backup heuristic: empty date but lots of totals-like columns
            if (!$isSummary) {
                $dateVal = $err['values']['date'] ?? $err['date'] ?? null;
                if ((empty($dateVal) || $dateVal === 'N/A') && is_string($nik) && str_contains(strtoupper($nik), 'TOTAL FOR EMPLOYEE')) {
                    $isSummary = true;
                }
            }

            if ($isSummary) {
                $summaryCount++;
                continue; // drop from real errors list
            }

            $filtered[] = $err;
        }

        $stats['errors'] = $filtered;
        // Keep original skipped but expose split metrics
        $stats['skipped_summary'] = $summaryCount;
        $stats['skipped_invalid'] = max(($stats['skipped'] ?? 0) - $summaryCount, 0);

        return $stats;
    }
}
