<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceType;
use App\Enums\MediaCollection;
use App\Enums\NotificationType;
use App\Enums\ScheduleType;
use App\Enums\UserType;
use App\Events\Attendance\AttendanceRequested;
use App\Exports\AttendanceReport;
use App\Http\Requests\Api\Attendance\ApproveAttendanceRequest;
use App\Http\Requests\Api\Attendance\ChildrenRequest;
use App\Http\Requests\Api\Attendance\ExportReportRequest;
use App\Http\Requests\Api\Attendance\IndexRequest;
use App\Http\Requests\Api\Attendance\ManualAttendanceRequest;
use App\Http\Requests\Api\Attendance\RequestAttendanceRequest;
use App\Http\Requests\Api\Attendance\StoreRequest;
use App\Http\Resources\Attendance\AttendanceDetailResource;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Http\Resources\DefaultResource;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\Event;
use App\Models\NationalHoliday;
use App\Models\TimeoffRegulation;
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

class AttendanceController extends BaseController
{
    const ALLOWED_INCLUDES = ['user', 'schedule', 'shift', 'details'];

    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:attendance_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:attendance_access', ['only' => ['restore']]);
        $this->middleware('permission:attendance_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:attendance_create', ['only' => ['store', 'request']]);
        $this->middleware('permission:attendance_edit', ['only' => 'update']);
        $this->middleware('permission:attendance_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    // public function report(ExportReportRequest $request, ?string $export = null)
    // {
    //     $startDate = Carbon::createFromFormat('Y-m-d', $request->filter['start_date']);
    //     $endDate = Carbon::createFromFormat('Y-m-d', $request->filter['end_date']);
    //     $dateRange = CarbonPeriod::create($startDate, $endDate);

    //     $users = User::tenanted(true)
    //         ->where('join_date', '<=', $startDate)
    //         ->where(fn ($q) => $q->whereNull('resign_date')->orWhere('resign_date', '>=', $endDate))
    //         ->get(['id', 'name']);
    //     return $users;
    //     $data = [];
    //     $totalData = 0;
    //     $totalDateRange = count($dateRange);
    //     $this->per_page = $totalDateRange > $this->per_page ? $totalDateRange : $this->per_page;
    //     foreach ($users as $user) {
    //         $attendances = Attendance::where('user_id', $user->id)
    //             ->with([
    //                 'shift' => fn ($q) => $q->select('id', 'name'),
    //                 'timeoff.timeoffPolicy',
    //                 'clockIn' => fn ($q) => $q->approved(),
    //                 'clockOut' => fn ($q) => $q->approved(),
    //             ])
    //             ->whereDateBetween($startDate, $endDate)
    //             ->limit($this->per_page)
    //             ->get();

    //         $schedule = ScheduleService::getTodaySchedule($user, $startDate);
    //         if ($schedule) {
    //             $order = $schedule->shifts->where('id', $schedule->shift->id);
    //             $orderKey = array_keys($order->toArray())[0];
    //             $totalShifts = $schedule->shifts->count();

    //             $companyHolidays = Event::tenanted()->whereHoliday()->get();
    //             $nationalHolidays = NationalHoliday::orderBy('date')->get();

    //             foreach ($dateRange as $date) {
    //                 if ($totalData >= $this->per_page) break;
    //                 // 1. kalo tgl merah(national holiday), shift nya pake tgl merah
    //                 // 2. kalo company event(holiday), shiftnya pake holiday
    //                 // 3. kalo schedulenya is_overide_national_holiday == false, shiftnya pake shift
    //                 // 4. kalo schedulenya is_overide_company_holiday == false, shiftnya pake shift
    //                 // 5. kalo ngambil timeoff, shfitnya tetap pake shift hari itu, munculin data timeoffnya
    //                 $date = $date->format('Y-m-d');
    //                 $attendance = $attendances->firstWhere('date', $date->format('Y-m-d'));

    //                 if ($attendance) {
    //                     $shift = $attendance->shift;

    //                     // load overtime
    //                     $totalOvertime = AttendanceService::getSumOvertimeDuration($user, $date);
    //                     $attendance->total_overtime = $totalOvertime;

    //                     // load task
    //                     $totalTask = TaskService::getSumDuration($user, $date);
    //                     $attendance->total_task = $totalTask;
    //                 } else {
    //                     $shift = $schedule->shifts[$orderKey];
    //                 }
    //                 $shiftType = 'shift';

    //                 $companyHolidayData = null;
    //                 if ($schedule->is_overide_company_holiday == false) {
    //                     $companyHolidayData = $companyHolidays->first(function ($companyHoliday) use ($date) {
    //                         return date('Y-m-d', strtotime($companyHoliday->start_at)) <= $date && date('Y-m-d', strtotime($companyHoliday->end_at)) >= $date;
    //                     });

    //                     if ($companyHolidayData) {
    //                         $shift = $companyHolidayData;
    //                         $shiftType = 'company_holiday';
    //                     }
    //                 }

    //                 if ($schedule->is_overide_national_holiday == false && is_null($companyHolidayData)) {
    //                     $nationalHoliday = $nationalHolidays->firstWhere('date', $date->format('Y-m-d'));));
    //                     if ($nationalHoliday) {
    //                         $shift = $nationalHoliday;
    //                         $shiftType = 'national_holiday';
    //                     }
    //                 }

    //                 unset($shift->pivot);

    //                 $data[] = [
    //                     'user' => $user,
    //                     'date' => $date,
    //                     'shift_type' => $shiftType,
    //                     'shift' => $shift,
    //                     'attendance' => $attendance
    //                 ];

    //                 if (($orderKey + 1) === $totalShifts) {
    //                     $orderKey = 0;
    //                 } else {
    //                     $orderKey++;
    //                 }

    //                 $totalData++;
    //             }
    //         }
    //     }

    //     return DefaultResource::collection($data);
    //     // dd($request->all());

    //     // $query = Attendance::select(['id', 'user_id', 'schedule_id', 'shift_id', 'timeoff_id', 'event_id', 'code', 'date', 'created_at'])
    //     //     ->with([
    //     //         'details' => fn ($q) => $q->select('id', 'attendance_id', 'is_clock_in', 'time', 'type', 'lat', 'lng', 'approval_status', 'approved_at', 'approved_by', 'note', 'created_at'),
    //     //         'user' => fn ($q) => $q->select('id', 'name')
    //     //     ])
    //     //     ->whereDateBetween($request->filter['start_date'], $request->filter['end_date']);

    //     // $attendances = QueryBuilder::for($query)
    //     //     ->allowedSorts(['user_id'])
    //     //     ->get();

    //     // $data = [];
    //     // foreach ($attendances as $attendance) {
    //     //     $data[] = $attendance;
    //     // }

    // }

    public function report(ExportReportRequest $request, ?string $export = null)
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $request->filter['start_date']);
        $endDate = Carbon::createFromFormat('Y-m-d', $request->filter['end_date']);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $companyHolidays = Event::tenanted()->whereHoliday()->get();
        $nationalHolidays = NationalHoliday::orderBy('date')->get();

        $users = User::tenanted(true)
            ->where('join_date', '<=', $startDate)
            ->where(fn($q) => $q->whereNull('resign_date')->orWhere('resign_date', '>=', $endDate))
            ->get(['id', 'name', 'nik']);

        $data = [];
        foreach ($users as $user) {
            $user->setAppends([]);
            $attendances = Attendance::where('user_id', $user->id)
                ->with([
                    'shift' => fn($q) => $q->select('id', 'name', 'is_dayoff', 'clock_in', 'clock_out'),
                    'timeoff.timeoffPolicy',
                    'clockIn' => fn($q) => $q->approved(),
                    'clockOut' => fn($q) => $q->approved(),
                ])
                ->whereDateBetween($startDate, $endDate)
                ->get();

            $dataAttendance = [];
            foreach ($dateRange as $date) {
                $date = $date->format('Y-m-d');
                $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name'], ['id', 'name', 'is_dayoff', 'clock_in', 'clock_out']);
                $attendance = $attendances->firstWhere('date', $date);
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
                    $overtimeDurationBeforeShift = AttendanceService::getSumOvertimeDuration(user: $user, startDate: $date, formatText: false, query: fn($q) => $q->where('is_after_shift', false));
                    $attendance->overtime_duration_before_shift = $overtimeDurationBeforeShift;

                    $overtimeDurationAfterShift = AttendanceService::getSumOvertimeDuration(user: $user, startDate: $date, formatText: false, query: fn($q) => $q->where('is_after_shift', true));
                    $attendance->overtime_duration_after_shift = $overtimeDurationAfterShift;

                    // load task
                    $totalTask = TaskService::getSumDuration($user, $date);
                    $attendance->total_task = $totalTask;

                    if (!$attendance->schedule->is_flexible && $attendance->schedule->is_include_late_in && $attendance->clockIn) {
                        $attendance->late_in = getIntervalTime($attendance->shift->clock_in, date('H:i:s', strtotime($attendance->clockIn->time)), true);
                    }

                    if (!$attendance->schedule->is_flexible && $attendance->schedule->is_include_early_out && $attendance->clockOut) {
                        $attendance->early_out = getIntervalTime(date('H:i:s', strtotime($attendance->clockOut->time)), $attendance->shift->clock_out, true);
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

        $timeoffRegulation = TimeoffRegulation::tenanted()->where('company_id', $user->company_id)->first(['id', 'cut_off_date']);

        $month = date('m');

        $year = isset($request->filter['year']) ? $request->filter['year'] : date('Y');
        $month = isset($request->filter['month']) ? $request->filter['month'] : $month;
        if (date('d') <= $timeoffRegulation->cut_off_date) {
            $month -= 1;
        }

        $startDate = date(sprintf('%s-%s-%s', $year, $month, $timeoffRegulation->cut_off_date));
        $endDate = date('Y-m-d', strtotime($startDate . '+1 month'));
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->addDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $endDate);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $data = [];

        $summaryPresentOnTime = 0;
        $summaryPresentLateClockIn = 0;
        $summaryPresentEarlyClockOut = 0;
        $summaryNotPresentAbsent = 0;
        $summaryNotPresentNoClockIn = 0;
        $summaryNotPresentNoClockOut = 0;
        $summaryAwayDayOff = 0;
        $summaryAwayTimeOff = 0;

        $schedule = ScheduleService::getTodaySchedule($user, $startDate)?->load(['shifts' => fn($q) => $q->orderBy('order')]);
        if ($schedule) {
            $order = $schedule->shifts->where('id', $schedule->shift->id);
            $orderKey = array_keys($order->toArray())[0];
            $totalShifts = $schedule->shifts->count();

            $attendances = Attendance::tenanted()
                ->where('user_id', $user->id)
                ->with([
                    'shift',
                    'timeoff.timeoffPolicy',
                    'clockIn',
                    'clockOut',
                    'details' => fn($q) => $q->orderBy('created_at')
                ])
                ->whereDateBetween($startDate, $endDate)
                ->get();

            $companyHolidays = Event::tenanted()->whereHoliday()->get();
            $nationalHolidays = NationalHoliday::orderBy('date')->get();

            foreach ($dateRange as $date) {
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
                    $shift = $schedule->shifts[$orderKey];
                    $summaryNotPresentAbsent += 1;
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

                $data[] = [
                    'date' => $date,
                    'shift_type' => $shiftType,
                    'shift' => $shift,
                    'attendance' => $attendance
                ];

                if (($orderKey + 1) === $totalShifts) {
                    $orderKey = 0;
                } else {
                    $orderKey++;
                }
            }
        }

        return response()->json([
            'summary' => [
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
            ],
            'data' => $data,
        ]);
    }

    public function employeesSummary(ChildrenRequest $request)
    {
        $user = auth('sanctum')->user();

        $query = User::select('id', 'name', 'nik');
        if (!$user->is_super_admin) {
            $query->tenanted();
            if (!$user->type->is(UserType::ADMINISTRATOR)) {
                $query->whereDescendantOf($user);
            }
        }

        // $users = QueryBuilder::for($query)
        //     ->allowedFilters([
        //         AllowedFilter::exact('id'),
        //         'nik',
        //         'name',
        //     ])
        //     ->allowedSorts([
        //         'nik',
        //         'name',
        //     ])
        //     ->get();

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

        // $companyHolidays = Event::tenanted()->whereHoliday()->get();
        // $nationalHolidays = NationalHoliday::orderBy('date')->get();

        foreach ($query->get() as $user) {
            $schedule = ScheduleService::getTodaySchedule($user, $date, scheduleType: $request->filter['schedule_type'] ?? ScheduleType::ATTENDANCE->value);

            $attendance = $user->attendances()
                ->where('date', $date)
                ->whereHas('schedule', function ($q) {
                    $q->where('schedules.type', $request->filter['schedule_type'] ?? ScheduleType::ATTENDANCE->value);
                })
                ->with([
                    'shift' => fn($q) => $q->select('id', 'clock_in', 'clock_out'),
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
                $shift = $schedule->shift ?? null;
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
        $user = auth('sanctum')->user();

        $query = User::select('id', 'name', 'nik');
        if (!$user->is_super_admin) {
            $query->tenanted();
            if (!$user->type->is(UserType::ADMINISTRATOR)) {
                $query->whereDescendantOf($user);
            }
        }

        $users = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::scope('schedule_type'),
                'nik',
                'name',
            ])
            ->allowedSorts([
                'nik',
                'name',
            ])
            ->paginate($this->per_page);

        $date = $request->filter['date'];

        $companyHolidays = Event::tenanted()->whereHoliday()->get();
        $nationalHolidays = NationalHoliday::orderBy('date')->get();
        $users->map(function ($user) use ($date, $companyHolidays, $nationalHolidays, $request) {
            $schedule = ScheduleService::getTodaySchedule($user, $date, scheduleType: $request->filter['schedule_type'] ?? ScheduleType::ATTENDANCE->value);

            $attendance = $user->attendances()
                ->where('date', $date)
                ->with([
                    'shift',
                    'timeoff.timeoffPolicy',
                    'clockIn',
                    'clockOut',
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
                $shift = $schedule->shift ?? null;
            }
            $shiftType = 'shift';

            $companyHolidayData = null;
            if ($schedule?->is_overide_company_holiday == false) {
                $companyHolidayData = $companyHolidays->first(function ($companyHoliday) use ($date) {
                    return date('Y-m-d', strtotime($companyHoliday->start_at)) <= $date && date('Y-m-d', strtotime($companyHoliday->end_at)) >= $date;
                });

                if ($companyHolidayData) {
                    $shift = $companyHolidayData;
                    $shiftType = 'company_holiday';
                }
            }

            if ($schedule?->is_overide_national_holiday == false && is_null($companyHolidayData)) {
                $nationalHoliday = $nationalHolidays->firstWhere('date', $date);
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
        $attendance = QueryBuilder::for(AttendanceDetail::whereHas('attendance', fn($q) => $q->tenanted()))
            ->allowedFilters([
                'is_clock_in',
                'approval_status',
                'approved_by',
                'type',
                AllowedFilter::callback('user_id', fn($query, $value) => $query->whereHas('attendance', fn($q) => $q->where('user_id', $value))),
                AllowedFilter::callback('shift_id', fn($query, $value) => $query->whereHas('attendance', fn($q) => $q->where('shift_id', $value))),
            ])
            ->allowedIncludes([
                AllowedInclude::callback('attendance', function ($query) {
                    $query->select('id', 'schedule_id', 'shift_id', 'code')
                        ->with('shift', fn($q) => $q->select('id', 'is_dayoff', 'name', 'clock_in', 'clock_out'));
                }),
            ])
            ->allowedSorts(['is_clock_in', 'approval_status', 'approved_by', 'created_at'])
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
        $attendance = AttendanceService::getTodayAttendance($request->schedule_id, $request->shift_id, $user, $request->time);

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
            return $this->errorResponse($e->getMessage());
        }

        return new AttendanceResource($attendance);
    }

    public function manualAttendance(ManualAttendanceRequest $request)
    {
        $user = User::find($request->user_id);

        $schedule = ScheduleService::getTodaySchedule($user, $request->date, scheduleType: ScheduleType::ATTENDANCE->value);
        $attendance = AttendanceService::getTodayAttendance($schedule->id, $request->shift_id, $user, $request->date);

        DB::beginTransaction();
        try {
            if (!$attendance) {
                $attendance = $user->attendances()->create([
                    'schedule_id' => $schedule->id,
                    'shift_id' => $request->shift_id,
                    'date' => $request->date,
                ]);
            }

            /** @var AttendanceDetail $attendanceDetail */
            // clock in
            $attendance->details()->create([
                'is_clock_in' => true,
                'time' => $request->date . ' ' . $request->clock_in,
                'type' => AttendanceType::MANUAL,
                'approval_status' => ApprovalStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => auth('sanctum')->id(),
            ]);
            // clock out
            $attendance->details()->create([
                'is_clock_in' => false,
                'time' => $request->date . ' ' . $request->clock_out,
                'type' => AttendanceType::MANUAL,
                'approval_status' => ApprovalStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => auth('sanctum')->id(),
            ]);

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
        $attendance = AttendanceService::getTodayAttendance($request->schedule_id, $request->shift_id, $user, $request->time);

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
        // pemeriksaan kehadiran hri ini
        $attendance = AttendanceService::getTodayAttendance($request->schedule_id, $request->shift_id, auth('sanctum')->user(), $request->date);

        DB::beginTransaction();
        try {
            // membuat data kehadiran baru jika tidak ada
            if (!$attendance) {
                $attendance = Attendance::create($request->validated());
            }

            if ($request->is_clock_in) {
                $attendanceDetailClockIn = $attendance->details()->create([
                    'is_clock_in' => true,
                    'time' => $request->date . ' ' . $request->clock_in_hour,
                    'type' => $request->type,
                    'note' => $request->note,
                ]);
                AttendanceRequested::dispatchIf($attendanceDetailClockIn->type->is(AttendanceType::MANUAL), $attendanceDetailClockIn);
            }

            if ($request->is_clock_out) {
                $attendanceDetailClockOut = $attendance->details()->create([
                    'is_clock_in' => false,
                    'time' => $request->date . ' ' . $request->clock_out_hour,
                    'type' => $request->type,
                    'note' => $request->note,
                ]);
                AttendanceRequested::dispatchIf($attendanceDetailClockOut->type->is(AttendanceType::MANUAL), $attendanceDetailClockOut);
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
        $total = DB::table('attendance_details')->where('approved_by', auth('sanctum')->id())->where('type', AttendanceType::MANUAL)->where('approval_status', $request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = AttendanceDetail::where('type', AttendanceType::MANUAL)
            ->whereHas('attendance.user', fn($q) => $q->where('approval_id', auth('sanctum')->id()))
            ->with('attendance', fn($q) => $q->select('id', 'user_id', 'shift_id', 'schedule_id')->with([
                'user' => fn($q) => $q->select('id', 'name'),
                'shift' => fn($q) => $q->select('id', 'name', 'is_dayoff', 'clock_in', 'clock_out'),
                'schedule' => fn($q) => $q->select('id', 'name')
            ]));

        $attendances = QueryBuilder::for($query)
            ->allowedFilters([
                'approval_status',
                'created_at',
            ])
            ->allowedSorts([
                'id',
                'approval_status',
                'created_at',
            ])
            ->paginate($this->per_page);

        return AttendanceResource::collection($attendances);
    }

    public function showApproval(AttendanceDetail $attendanceDetail)
    {
        $attendanceDetail->load(
            [
                'attendance' => fn($q) => $q->select('id', 'user_id', 'shift_id', 'schedule_id')
                    ->with([
                        'user' => fn($q) => $q->select('id', 'name'),
                        'shift' => fn($q) => $q->select('id', 'name', 'is_dayoff', 'clock_in', 'clock_out'),
                        'schedule' => fn($q) => $q->select('id', 'name')
                    ])
            ]
        );

        return new AttendanceDetailResource($attendanceDetail);
    }

    public function approve(AttendanceDetail $attendanceDetail, ApproveAttendanceRequest $request)
    {
        if (!$attendanceDetail->approval_status->is(ApprovalStatus::PENDING)) {
            return $this->errorResponse(message: 'Status can not be changed', code: \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $attendanceDetail->update($request->validated());

        $notificationType = NotificationType::ATTENDANCE_APPROVED;
        $attendanceDetail->attendance->user->notify(new ($notificationType->getNotificationClass())($notificationType, $attendanceDetail->approvedBy, $attendanceDetail->approval_status, $attendanceDetail));

        return new AttendanceDetailResource($attendanceDetail);
    }
}
