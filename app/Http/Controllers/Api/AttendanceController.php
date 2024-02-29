<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Attendance\IndexRequest;
use App\Http\Requests\Api\Attendance\RequestAttendanceRequest;
use App\Http\Requests\Api\Attendance\StoreRequest;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Models\Attendance;
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
        $schedule = ScheduleService::getTodaySchedule();
        return $schedule->load('shifts');
        $timeoffRegulation = TimeoffRegulation::tenanted()->first();
        $startDate = date(sprintf('%s-%s-%s', $request->filter['year'], $request->filter['month'], $timeoffRegulation->cut_off_date));
        $endDate = date('Y-m-d', strtotime($startDate . '+1 month'));
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->addDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $endDate);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $attendances = Attendance::tenanted()
            ->with('details', fn ($q) => $q->orderBy('created_at'))
            ->whereDateBetween($startDate, $endDate)
            ->get();
        // dd($attendances);

        $data = [];
        foreach ($dateRange as $date) {
            $date = $date->format('Y-m-d');
            // $data[$date] = $attendances->first(fn ($attendance) => $attendance->date == $date);
            $data[] = [
                'date' => $date,
                'attendance' => $attendances->first(fn ($attendance) => $attendance->date == $date)
            ];
            // dd($date->format('Y-m-d'));
        }

        return response()->json($data);
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
            $attendance = AttendanceService::getTodayAttendance($request->schedule_id, $request->shift_id, auth('sanctum')->user(), $request->time);

            // membuat data kehadiran baru jika tidak ada
            if (!$attendance) {
                $data = [
                    'date' => date('Y-m-d', strtotime($request->time)),
                    ...$request->validated(),
                ];
                $attendance = Attendance::create($data);
            }

            // membuat detail untuk clock in dan clock out secara bersamaan
            $data = array_merge($request->validated(), ['is_clock_in' => true]);
            $data = array_merge($request->validated(), ['is_clock_out' => true]);

            $attendance->details()->create($request->validated());

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
