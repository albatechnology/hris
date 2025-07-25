<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Enums\ScheduleType;
use App\Http\Requests\Api\UserPatrolTask\StoreRequest;
use App\Http\Requests\Api\UserPatrolTask\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\UserPatrolTask;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class UserPatrolTaskController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:patrol_access', ['only' => ['restore']]);
        $this->middleware('permission:patrol_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:patrol_create', ['only' => 'store']);
        $this->middleware('permission:patrol_edit', ['only' => 'update']);
        $this->middleware('permission:patrol_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(UserPatrolTask::tenanted())
            ->allowedIncludes([
                'schedule',
                'shift',
                'media',
                AllowedInclude::callback('patrolTask', function ($q) {
                    $q->select('id', 'patrol_location_id', 'name')->with('patrolLocation', fn($q) => $q->select('id', 'branch_location_id')->with('branchLocation', fn($q) => $q->select('id', 'name')));
                })
            ])
            ->allowedFilters([
                AllowedFilter::exact('user_patrol_batch_id'),
                AllowedFilter::exact('patrol_task_id'),
                AllowedFilter::exact('schedule_id'),
                AllowedFilter::exact('shift_id'),
                AllowedFilter::callback('patrol_location_id', function ($query, $value) {
                    $query->whereHas('patrolTask', fn($q) => $q->where('patrol_location_id', $value));
                }),
                // AllowedFilter::callback('search', function ($query, $value) {
                //     $query->whereHas('user', fn($q) => $q->whereLike('name', $value));
                // }),
                AllowedFilter::callback('date', function ($query, $value) {
                    $query->whereDate('created_at', $value);
                }),
                AllowedFilter::callback('patrol_id', function ($query, $value) {
                    $query->whereHas('patrolTask', fn($q) => $q->whereHas('patrolLocation', fn($q) => $q->where('patrol_id', $value)));
                }),
            ])
            ->allowedSorts([
                'id',
                'user_patrol_batch_id',
                'patrol_task_id',
                'schedule_id',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $userPatrolTask = UserPatrolTask::findTenanted($id);
        $userPatrolTask->load(['patrolTask', 'schedule', 'shift', 'media']);

        return new DefaultResource($userPatrolTask);
    }

    public function store(StoreRequest $request)
    {
        try {
            // Get current schedule
            $schedule = ScheduleService::getTodaySchedule(scheduleType: ScheduleType::PATROL->value);

            // Define start and end times based on shift clock_in and clock_out
            $start = Carbon::createFromFormat('H:i:s', $schedule->shift->clock_in);
            $end = Carbon::createFromFormat('H:i:s', $schedule->shift->clock_out);

            $currentTime = Carbon::now(); // Current time
            $currentPeriod = null;

            // Generate 2-hour intervals within the shift time
            while ($start->lt($end)) {
                $nextPeriod = $start->copy()->addMinutes(5);

                // Check if the current time falls within this period
                if ($currentTime->between($start, $nextPeriod)) {
                    $currentPeriod = [$start, $nextPeriod];
                    break;
                }

                // Move to the next period
                $start->addMinutes(5);
            }

            if (!$schedule?->shift) {
                return response()->json(['message' => 'Schedule not found'], Response::HTTP_NOT_FOUND);
            }

            $checkPatrol = auth('sanctum')->user()->patrols()
                ->whereDate('patrols.start_date', '<=', now())
                ->whereDate('patrols.end_date', '>=', now())
                ->whereHas('patrolLocations.tasks', function ($q) use ($request) {
                    $q->where('patrol_tasks.id', $request->patrol_task_id);
                })
                ->first();

            if (!$checkPatrol) {
                return $this->errorResponse('Invalid patrol task');
            }

            if ($currentPeriod) {
                $checkUserPatrolTask = UserPatrolTask::where('patrol_task_id', $request->patrol_task_id)
                    ->where('schedule_id', $schedule->id)
                    ->where('shift_id', $schedule->shift->id)
                    ->whereBetween('created_at', [$currentPeriod[0]->toDateTimeString(), $currentPeriod[1]->toDateTimeString()])
                    // ->whereHas('patrolTask', fn($q) => $q->whereNotIn('status', [PatrolTaskStatus::CANCEL->value]))
                    ->orderBy('id', 'DESC')
                    ->first();
            }

            if ($checkUserPatrolTask) {
                return $this->errorResponse('Task have been submitted in this period');
            }

            $userPatrolTask = auth('sanctum')->user()->userPatrolTasks()->create([
                'patrol_task_id' => $request->patrol_task_id,
                'description' => $request->description,
                'schedule_id' => $schedule->id,
                'shift_id' => $schedule->shift->id,
                'lat' => $request->lat,
                'lng' => $request->lng,
            ]);

            if ($request->hasFile('file')) {
                foreach ($request->file('file') as $file) {
                    if ($file->isValid()) {
                        $userPatrolTask->addMedia($file)->toMediaCollection(MediaCollection::DEFAULT->value);
                    }
                }
            }

            // PatrolTask::where('id', $request->patrol_task_id)->update([
            //     'status' => PatrolTaskStatus::COMPLETE,
            // ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($userPatrolTask->load('media'));
    }

    public function update(int $id, UpdateRequest $request)
    {
        $userPatrolTask = auth('sanctum')->user()->userPatrolTasks()->firstWhere('id', $id);

        try {
            $userPatrolTask->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($userPatrolTask->load('media')))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $userPatrolTask = auth('sanctum')->user()->userPatrolTasks()->firstWhere('id', $id);
        $userPatrolTask->delete();

        return $this->deletedResponse();
    }
}
