<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PatrolTask\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Patrol;
use App\Models\PatrolLocation;
use App\Models\PatrolTask;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PatrolTaskController extends BaseController
{
    private PatrolLocation $patrolLocation;

    public function __construct()
    {
        parent::__construct();
        $this->patrolLocation = PatrolLocation::tenanted()
            ->where('id', request()->segment(5))
            ->where('patrol_id', request()->segment(3))
            ->firstOrFail(['id']);

        $this->middleware('permission:patrol_task_access', ['only' => ['restore']]);
        $this->middleware('permission:patrol_task_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:patrol_task_create', ['only' => 'store']);
        $this->middleware('permission:patrol_task_edit', ['only' => 'update']);
        $this->middleware('permission:patrol_task_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(int $patrolId, int $patrolLocationId)
    {
        $data = QueryBuilder::for(PatrolTask::where('patrol_location_id', $this->patrolLocation->id))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('patrol_location_id'),
                'name'
            ])
            ->allowedSorts([
                'id',
                'patrol_location_id',
                'name',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $patrolId, int $patrolLocationId, int $id)
    {
        $patrolTask = $this->patrolLocation->tasks()->findOrFail($id);
        $patrolTask->load('patrolLocation');

        return new DefaultResource($patrolTask);
    }

    public function store(int $patrolId, int $patrolLocationId, StoreRequest $request)
    {
        $patrolTask = $this->patrolLocation->tasks()->create($request->validated());

        return new DefaultResource($patrolTask);
    }

    public function update(int $patrolId, int $patrolLocationId, int $id, StoreRequest $request)
    {
        $patrolTask = $this->patrolLocation->tasks()->findOrFail($id);
        $patrolTask->update($request->validated());

        return (new DefaultResource($patrolTask))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $patrolId, int $patrolLocationId, int $id)
    {
        $patrolTask = $this->patrolLocation->tasks()->findOrFail($id);
        $patrolTask->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $patrolId, int $patrolLocationId, $id)
    {
        $patrolTask = $this->patrolLocation->tasks()->withTrashed()->findOrFail($id);
        $patrolTask->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $patrolId, int $patrolLocationId, $id)
    {
        $patrolTask = $this->patrolLocation->tasks()->withTrashed()->findOrFail($id);
        $patrolTask->restore();

        return new DefaultResource($patrolTask);
    }
}
