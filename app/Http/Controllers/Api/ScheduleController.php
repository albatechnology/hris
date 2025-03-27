<?php

namespace App\Http\Controllers\Api;

use App\Exports\ImportScheduleShiftsExport;
use App\Http\Requests\Api\Schedule\StoreRequest;
use App\Http\Requests\Api\Schedule\TodayScheduleRequest;
use App\Http\Resources\Schedule\ScheduleResource;
use App\Http\Resources\Schedule\TodayScheduleResource;
use App\Imports\ImportShiftsImport;
use App\Models\Schedule;
use App\Services\AttendanceService;
use App\Services\ScheduleService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ScheduleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:schedule_access', ['only' => ['restore']]);
        $this->middleware('permission:schedule_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:schedule_create', ['only' => 'store']);
        $this->middleware('permission:schedule_edit', ['only' => 'update']);
        $this->middleware('permission:schedule_delete', ['only' => ['destroy', 'forceDelete']]);

        // $this->middleware('permission:attendance_create', ['only' => 'today']);
    }

    public function index()
    {
        $data = QueryBuilder::for(Schedule::tenanted()->whereApproved())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('created_by'),
                'name',
                'type',
                'effective_date',
                // AllowedFilter::exact('approved_by'),
                // 'approval_status',
                // 'approved_at',
                // 'is_need_approval',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'created_by',
                'name',
                'effective_date',
                'created_at',
                // 'approved_by',
                // 'approval_status',
                // 'approved_at',
                // 'is_need_approval',
            ])
            ->paginate($this->per_page);

        return ScheduleResource::collection($data);
    }

    public function show(int $id)
    {
        $schedule = Schedule::findTenanted($id);
        return new ScheduleResource($schedule->load(['shifts' => fn($q) => $q->orderBy('order')]));
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $schedule = Schedule::create($request->validated());

            $order = 1;
            foreach ($request->shifts ?? [] as $shift) {
                $schedule->shifts()->attach($shift['id'], ['order' => $order++]);
            }

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new ScheduleResource($schedule);
    }

    public function update(int $id, StoreRequest $request)
    {
        $schedule = Schedule::findTenanted($id);
        DB::beginTransaction();
        try {
            $schedule->shifts()->sync([]);
            $schedule->update($request->validated());

            $order = 1;
            foreach ($request->shifts ?? [] as $shift) {
                $schedule->shifts()->attach($shift['id'], ['order' => $order++]);
            }

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return (new ScheduleResource($schedule))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $schedule = Schedule::findTenanted($id);
        $schedule->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $schedule = Schedule::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $schedule->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $schedule = Schedule::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $schedule->restore();

        return new ScheduleResource($schedule);
    }

    public function today(TodayScheduleRequest $request)
    {
        $schedule = ScheduleService::getTodaySchedule(datetime: $request->date, shiftColumn: ['id', 'name', 'is_dayoff', 'type', 'clock_in', 'clock_out']);

        if (!$schedule) {
            return response()->json(['message' => 'Schedule not found'], Response::HTTP_NOT_FOUND);
        }

        if ($request->include) {
            $includes = explode(',', $request->include);
            if (in_array('shifts', $includes)) {
                $schedule->load(['shifts' => fn($q) => $q->selectMinimalist()]);
            }

            if (in_array('attendance', $includes)) {
                $select = fn($q) => $q->select('id', 'attendance_id', 'time');
                $attendance = AttendanceService::getTodayAttendance($request->date);
                if ($attendance) {
                    $attendance->load(['clockIn' => $select, 'clockOut' => $select]);
                    $schedule->attendance = $attendance;
                }
            }
        }

        return new TodayScheduleResource($schedule);
    }

    public function downloadTemplateImport(int $id, Request $request)
    {
        $schedule = Schedule::findTenanted($id);

        return Excel::download(new ImportScheduleShiftsExport($schedule), 'import shifts - ' . $schedule->name . '.xlsx');
    }

    public function importShifts(int $id, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx',
        ]);

        $schedule = Schedule::findTenanted($id);

        Excel::import(new ImportShiftsImport($schedule), $request->file);
        return $this->updatedResponse();
    }
}
