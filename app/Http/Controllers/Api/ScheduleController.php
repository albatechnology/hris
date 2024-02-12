<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Schedule\ScheduleShiftRequest;
use App\Http\Requests\Api\Schedule\StoreRequest;
use App\Http\Resources\Schedule\ScheduleResource;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Response;
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
                'name', 'effective_date'
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'name', 'effective_date', 'created_at'
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
        $schedule = Schedule::create($request->validated());

        return new ScheduleResource($schedule);
    }

    public function update(Schedule $schedule, StoreRequest $request)
    {
        $schedule->update($request->validated());

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

    public function updateShifts(Schedule $schedule, ScheduleShiftRequest $request)
    {
        $data = [];
        foreach ($request->shifts ?? [] as $shift) {
            $data[$shift['id']] = ['order' => $shift['order']];
        }
        $schedule->shifts()->sync($data);

        return new ScheduleResource($schedule->load(['shifts' => fn ($q) => $q->orderBy('order')]));
    }

    public function today()
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        $schedule = $user->schedules()->whereDate('effective_date', '<=', date('Y-m-d'))->orderByDesc('effective_date')->first();
        if (!$schedule) return response()->json(['message' => 'Schedule not found'], Response::HTTP_NOT_FOUND);

        return new ScheduleResource($schedule->load('shift.shift'));

        // $schedule->load(['shifts' => fn ($q) => $q->orderByDesc('order')->limit(1)]);
        // return new ScheduleResource($schedule);
        // if ($schedule->shifts->count() <= 0) return response()->json(['message' => 'Shift not found'], Response::HTTP_NOT_FOUND);
        // return response()->json($schedule);
        // $shift = $schedule->shifts->first();
        // dd($shift);
        // return new ShiftResource($shift);
    }
}