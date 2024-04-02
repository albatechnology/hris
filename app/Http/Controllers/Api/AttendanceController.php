<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceType;
use App\Enums\MediaCollection;
use App\Enums\NotificationType;
use App\Events\Attendance\AttendanceRequested;
use App\Http\Requests\Api\Attendance\ApproveAttendanceRequest;
use App\Http\Requests\Api\Attendance\IndexRequest;
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
use App\Services\AttendanceService;
use App\Services\Aws\Rekognition;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\DB;
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

    public function index(IndexRequest $request)
    {
        $timeoffRegulation = TimeoffRegulation::tenanted()->first(['id', 'cut_off_date']);

        $startDate = date(sprintf('%s-%s-%s', $request->filter['year'], $request->filter['month'], $timeoffRegulation->cut_off_date));
        $endDate = date('Y-m-d', strtotime($startDate . '+1 month'));
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->addDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $endDate);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $data = [];
        $schedule = ScheduleService::getTodaySchedule(date: $startDate)?->load(['shifts' => fn ($q) => $q->orderBy('order')]);
        if ($schedule) {
            $userId = auth('sanctum')->id();
            $order = $schedule->shifts->where('id', $schedule->shift->id);
            $orderKey = array_keys($order->toArray())[0];
            $totalShifts = $schedule->shifts->count();

            $attendances = Attendance::tenanted()
                ->where('user_id', $userId)
                ->with([
                    'shift',
                    'timeoff.timeoffPolicy',
                    'clockIn',
                    'clockOut',
                    'details' => fn ($q) => $q->orderBy('created_at')
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

                    // load overtime
                    $totalOvertime = AttendanceService::getSumOvertimeDuration($userId, $date);
                    $attendance->total_overtime = $totalOvertime;
                } else {
                    $shift = $schedule->shifts[$orderKey];
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
            'data' => $data
        ]);
    }

    public function logs()
    {
        $attendance = QueryBuilder::for(AttendanceDetail::whereHas('attendance', fn ($q) => $q->tenanted()))
            ->allowedFilters([
                'is_clock_in', 'is_approved', 'approved_by',
                AllowedFilter::callback('user_id', fn ($query, $value) => $query->whereHas('attendance', fn ($q) => $q->where('user_id', $value))),
                AllowedFilter::callback('shift_id', fn ($query, $value) => $query->whereHas('attendance', fn ($q) => $q->where('shift_id', $value))),
            ])
            ->allowedIncludes([
                AllowedInclude::callback('attendance', function ($query) {
                    $query->select('id', 'schedule_id', 'shift_id', 'code')
                        ->with('shift', fn ($q) => $q->select('id', 'is_dayoff', 'name', 'clock_in', 'clock_out'));
                }),
            ])
            ->allowedSorts(['is_clock_in', 'is_approved', 'approved_by', 'created_at'])
            ->paginate($this->per_page);

        return new DefaultResource($attendance);
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

    public function approvals()
    {
        $query = AttendanceDetail::where('type', AttendanceType::MANUAL)
            ->whereHas('attendance.user', fn ($q) => $q->where('manager_id', auth('sanctum')->id()))
            ->with('attendance', fn ($q) => $q->select('id', 'user_id', 'shift_id', 'schedule_id')->with([
                'user' => fn ($q) => $q->select('id', 'name','image'),
            ]));

        $attendances = QueryBuilder::for($query)
            ->allowedSorts([
                'id', 'is_approved', 'created_at',
            ])
            ->paginate($this->per_page);

        return AttendanceResource::collection($attendances);
    }

    public function showApproval(AttendanceDetail $attendanceDetail)
    {
        $attendanceDetail->load(
            [
                'attendance' => fn ($q) => $q->select('id', 'user_id', 'shift_id', 'schedule_id')
                    ->with([
                        'user' => fn ($q) => $q->select('id', 'name'),
                        'shift' => fn ($q) => $q->select('id', 'name', 'is_dayoff'),
                        'schedule' => fn ($q) => $q->select('id', 'name')
                    ])
            ]
        );
        $attendanceDetail->attendance->user?->append('image');

        return new AttendanceDetailResource($attendanceDetail);
    }

    public function approve(AttendanceDetail $attendanceDetail, ApproveAttendanceRequest $request)
    {
        if ($attendanceDetail->is_approved == $request->is_approved) {
            return response()->json([
                'message' => 'Attendance is already ' . ($attendanceDetail->is_approved ? 'approved' : 'rejected'),
            ]);
        }

        $attendanceDetail->update($request->validated());

        $notificationType = NotificationType::ATTENDANCE_APPROVED;
        $attendanceDetail->attendance->user->notify(new ($notificationType->getNotificationClass())($notificationType, $attendanceDetail->approvedBy, $attendanceDetail->is_approved, $attendanceDetail));

        return new AttendanceDetailResource($attendanceDetail);
    }
}
