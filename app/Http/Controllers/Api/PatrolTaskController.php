<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PatrolTask\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\PatrolLocation;
use App\Models\PatrolTask;
use Exception;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PatrolTaskController extends BaseController
{
    private PatrolLocation $patrolLocation;

    public function __construct()
    {
        parent::__construct();
        $this->patrolLocation = PatrolLocation::where('id', request()->segment(5))
            ->where('patrol_id', request()->segment(3))
            ->firstOrFail(['id']);

        $this->middleware('permission:patrol_access', ['only' => ['restore']]);
        $this->middleware('permission:patrol_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:patrol_create', ['only' => 'store']);
        $this->middleware('permission:patrol_edit', ['only' => 'update']);
        $this->middleware('permission:patrol_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(int $patrolId, int $patrolLocationId)
    {
        $data = QueryBuilder::for(PatrolTask::where('patrol_location_id', $this->patrolLocation->id))
            ->with('patrolLocation.branchLocation')
            ->allowedFilters([
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
        $patrolTask->load('patrolLocation.branchLocation');

        return new DefaultResource($patrolTask);
    }

    public function store(int $patrolId, int $patrolLocationId, StoreRequest $request)
    {
        try {
            $patrolTask = $this->patrolLocation->tasks()->create($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($patrolTask);
    }

    public function update(int $patrolId, int $patrolLocationId, int $id, StoreRequest $request)
    {
        $patrolTask = $this->patrolLocation->tasks()->findOrFail($id);

        try {
            $patrolTask->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($patrolTask))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $patrolId, int $patrolLocationId, int $id)
    {
        $patrolTask = $this->patrolLocation->tasks()->findOrFail($id);

        try {
            $patrolTask->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete(int $patrolId, int $patrolLocationId, $id)
    {
        $patrolTask = $this->patrolLocation->tasks()->withTrashed()->findOrFail($id);

        try {
            $patrolTask->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore(int $patrolId, int $patrolLocationId, $id)
    {
        $patrolTask = $this->patrolLocation->tasks()->withTrashed()->findOrFail($id);

        try {
            $patrolTask->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($patrolTask);
    }
}
