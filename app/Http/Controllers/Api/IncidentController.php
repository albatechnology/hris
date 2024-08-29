<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Incident\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Incident;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:incident_access', ['only' => ['restore']]);
        $this->middleware('permission:incident_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:incident_create', ['only' => 'store']);
        $this->middleware('permission:incident_edit', ['only' => 'update']);
        $this->middleware('permission:incident_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Incident::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('client_location_id'),
            ])
            ->allowedSorts([
                'id', 'user_id', 'client_location_id', 'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(Incident $incident)
    {
        $incident->load(['user', 'clientLocation']);
        return new DefaultResource($incident);
    }

    public function store(StoreRequest $request)
    {
        $incident = Incident::create($request->validated());

        return new DefaultResource($incident);
    }

    public function update(Incident $incident, StoreRequest $request)
    {
        $incident->update($request->validated());

        return (new DefaultResource($incident))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Incident $incident)
    {
        $incident->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $incident = Incident::withTrashed()->findOrFail($id);
        $incident->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $incident = Incident::withTrashed()->findOrFail($id);
        $incident->restore();

        return new DefaultResource($incident);
    }
}
