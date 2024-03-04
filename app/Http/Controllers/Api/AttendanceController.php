<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Attendance\IndexRequest;
use App\Http\Requests\Api\Attendance\RequestAttendanceRequest;
use App\Http\Requests\Api\Attendance\StoreRequest;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\NationalHoliday;
use App\Models\TimeoffRegulation;
use App\Services\AttendanceService;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class AttendanceController extends BaseController
{
    const ALLOWED_INCLUDES = ['user', 'schedule', 'shift'];

    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:attendance_access', ['only' => ['index', 'show', 'restore']]);
        // $this->middleware('permission:attendance_access', ['only' => ['restore']]);
        // $this->middleware('permission:attendance_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:attendance_create', ['only' => 'store']);
        // $this->middleware('permission:attendance_edit', ['only' => 'update']);
        // $this->middleware('permission:attendance_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    // public function index()
    // {
    //     $attendances = QueryBuilder::for(Attendance::tenanted())
    //         ->allowedFilters([
    //             AllowedFilter::exact('id'),
    //             AllowedFilter::exact('user_id'),
    //             AllowedFilter::exact('schedule_id'),
    //             AllowedFilter::exact('shift_id'),
    //             AllowedFilter::scope('company_id', 'whereCompanyId'),
    //             AllowedFilter::scope('shift_id', 'whereShiftId'),
    //             'is_clock_in', 'time', 'type', 'is_approved', 'approved_by',
    //         ])
    //         ->allowedIncludes(self::ALLOWED_INCLUDES)
    //         ->allowedSorts([
    //             'id', 'user_id', 'schedule_id', 'shift_id', 'is_clock_in', 'time', 'type', 'is_approved', 'approved_by', 'created_at',
    //         ])
    //         ->paginate($this->per_page);

    //     return AttendanceResource::collection($attendances);
    // }

    public function index(IndexRequest $request)
    {
        $timeoffRegulation = TimeoffRegulation::tenanted()->first(['id', 'cut_off_date']);

        $startDate = date(sprintf('%s-%s-%s', $request->filter['year'], $request->filter['month'], $timeoffRegulation->cut_off_date));
        $endDate = date('Y-m-d', strtotime($startDate . '+1 month'));
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->addDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $endDate);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $schedule = ScheduleService::getTodaySchedule(date: $startDate)->load(['shifts' => fn ($q) => $q->orderBy('order')]);

        $order = $schedule->shifts->where('id', $schedule->shift->id);
        $orderKey = array_keys($order->toArray())[0];
        $totalShifts = $schedule->shifts->count();

        // return [
        //     'schedule' => $schedule,
        //     'shift' => $schedule->shifts[0]->pivot->order,
        //     'startDate' => $startDate,
        //     'endDate' => $endDate,
        //     'dateRange' => $dateRange,
        // ];
        $attendances = Attendance::tenanted()
            ->with([
                'shift',
                'timeoff.timeoffPolicy',
                'details' => fn ($q) => $q->orderBy('created_at')
            ])
            ->whereDateBetween($startDate, $endDate)
            ->get();

        $companyHolidays = Event::tenanted()->whereHoliday()->get();
        $nationalHolidays = NationalHoliday::orderBy('date')->get();

        $data = [];
        foreach ($dateRange as $date) {
            // 1. kalo tgl merah(national holiday), shift nya pake tgl merah
            // 2. kalo company event(holiday), shiftnya pake holiday
            // 3. kalo schedulenya !is_overide_national_holiday, shiftnya pake shift
            // 4. kalo schedulenya !is_overide_company_holiday, shiftnya pake shift
            // 5. kalo ngambil timeoff, shfitnya tetap pake shift hari itu, munculin data timeoffnya
            $date = $date->format('Y-m-d');
            $attendance = $attendances->firstWhere('date', $date);

            if ($attendance) {
                $shift = $attendance->shift;
            } else {
                $shift = $schedule->shifts[$orderKey];
            }
            $shiftType = 'shift';

            $companyHolidayData = null;
            if (!$schedule->is_overide_company_holiday) {
                $companyHolidayData = $companyHolidays->first(function ($companyHoliday) use ($date) {
                    return date('Y-m-d', strtotime($companyHoliday->start_at)) <= $date && date('Y-m-d', strtotime($companyHoliday->end_at)) >= $date;
                });

                if ($companyHolidayData) {
                    $shift = $companyHolidayData;
                    $shiftType = 'company_holiday';
                }
            }

            if (!$schedule->is_overide_national_holiday && is_null($companyHolidayData)) {
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

        return response()->json([
            'data' => $data
        ]);
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
        $attendance = AttendanceService::getTodayAttendance($request->schedule_id, $request->shift_id, auth('sanctum')->user(), $request->time);

        DB::beginTransaction();
        try {
            if (!$attendance) {
                $data = [
                    'date' => date('Y-m-d', strtotime($request->time)),
                    ...$request->validated(),
                ];
                $attendance = Attendance::create($data);
            }

            $attendance->details()->create($request->validated());
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new AttendanceResource($attendance);
    }

    public function request(RequestAttendanceRequest $request)
    {
        DB::beginTransaction();
        try {
            // pemeriksaan kehadiran hri ini
            $attendance = AttendanceService::getTodayAttendance($request->schedule_id, $request->shift_id, auth('sanctum')->user(), $request->date);

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
            }

            if ($request->is_clock_out) {
                $attendance->details()->create([
                    'is_clock_in' => false,
                    'time' => $request->date . ' ' . $request->clock_out_hour,
                    'type' => $request->type,
                    'note' => $request->note,
                ]);
            }

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
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
}
