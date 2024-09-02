<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PatrolLocation\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Patrol;
use App\Models\PatrolLocation;
use Exception;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PatrolLocationController extends BaseController
{
    private Patrol $patrol;

    public function __construct()
    {
        parent::__construct();
        $this->patrol = Patrol::tenanted()->where('id', request()->segment(3))->firstOrFail(['id']);

        $this->middleware('permission:patrol_access', ['only' => ['restore']]);
        $this->middleware('permission:patrol_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:patrol_create', ['only' => 'store']);
        $this->middleware('permission:patrol_edit', ['only' => 'update']);
        $this->middleware('permission:patrol_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(int $patrolId)
    {
        $data = QueryBuilder::for(PatrolLocation::where('patrol_id', $this->patrol->id))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('patrol_id'),
                AllowedFilter::exact('client_location_id'),
            ])
            ->allowedSorts([
                'id', 'patrol_id', 'client_location_id', 'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $patrolId, int $id)
    {
        $patrolLocation = $this->patrol->locations()->findOrFail($id);
        $patrolLocation->load(['patrol', 'clientLocation']);

        return new DefaultResource($patrolLocation);
    }

    public function store(int $patrolId, StoreRequest $request)
    {
        try {
            $patrolLocation = $this->patrol->locations()->create($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($patrolLocation);
    }

    public function update(int $patrolId, int $id, StoreRequest $request)
    {
        $patrolLocation = $this->patrol->locations()->findOrFail($id);

        try {
            $patrolLocation->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($patrolLocation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $patrolId, int $id)
    {
        $patrolLocation = $this->patrol->locations()->findOrFail($id);

        try {
            $patrolLocation->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete(int $patrolId, $id)
    {
        $patrolLocation = $this->patrol->locations()->withTrashed()->findOrFail($id);

        try {
            $patrolLocation->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore(int $patrolId, $id)
    {
        $patrolLocation = $this->patrol->locations()->withTrashed()->findOrFail($id);

        try {
            $patrolLocation->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($patrolLocation);
    }
}
