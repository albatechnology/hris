<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Schedule\StoreRequest;
use App\Http\Requests\Api\Schedule\TodayScheduleRequest;
use App\Http\Resources\Schedule\ScheduleResource;
use App\Models\Schedule;
use App\Services\ScheduleService;
use DateTime;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ScheduleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:schedule_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:schedule_access', ['only' => ['restore']]);
        $this->middleware('permission:schedule_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:schedule_create', ['only' => 'store']);
        $this->middleware('permission:schedule_edit', ['only' => 'update']);
        $this->middleware('permission:schedule_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Schedule::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                'name', 'effective_date',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'name', 'effective_date', 'created_at',
            ])
            ->paginate($this->per_page);

        return ScheduleResource::collection($data);
    }

    public function show(Schedule $schedule)
    {
        return new ScheduleResource($schedule->load(['shifts' => fn ($q) => $q->orderBy('order')]));
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $schedule = Schedule::create($request->validated());

            $data = [];
            foreach ($request->shifts ?? [] as $shift) {
                $data[$shift['id']] = ['order' => $shift['order']];
            }
            $schedule->shifts()->sync($data);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new ScheduleResource($schedule);
    }

    public function update(Schedule $schedule, StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $schedule->update($request->validated());

            $data = [];
            foreach ($request->shifts ?? [] as $shift) {
                $data[$shift['id']] = ['order' => $shift['order']];
            }
            $schedule->shifts()->sync($data);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return (new ScheduleResource($schedule))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $schedule = Schedule::withTrashed()->findOrFail($id);
        $schedule->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $schedule = Schedule::withTrashed()->findOrFail($id);
        $schedule->restore();

        return new ScheduleResource($schedule);
    }

    // public function updateShifts(Schedule $schedule, ScheduleShiftRequest $request)
    // {
    //     $data = [];
    //     foreach ($request->shifts ?? [] as $shift) {
    //         $data[$shift['id']] = ['order' => $shift['order']];
    //     }
    //     $schedule->shifts()->sync($data);

    //     return new ScheduleResource($schedule->load(['shifts' => fn ($q) => $q->orderBy('order')]));
    // }

    public function today(TodayScheduleRequest $request)
    {
        $schedule = ScheduleService::getTodaySchedule(date: $request->date);

        if (!$schedule) {
            return response()->json(['message' => 'Schedule not found'], Response::HTTP_NOT_FOUND);
        }

        $startDate = new DateTime($schedule->effective_date);
        $endDate = new DateTime();
        $interval = $startDate->diff($endDate)->days + 1;
        $sisaBagi = $interval % $schedule->shifts->count();

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'interval' => $interval,
            'sisaBagi' => $sisaBagi,
            'effective_date' => $schedule->effective_date,
            'total_shift' => $schedule->shifts->count(),
        ];
        return $schedule;
        return $schedule->shifts;

        return new ScheduleResource($schedule->load('shift.shift'));
    }
}
