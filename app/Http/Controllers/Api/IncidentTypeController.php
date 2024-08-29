<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\IncidentType\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\IncidentType;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentTypeController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:incident_type_access', ['only' => ['restore']]);
        $this->middleware('permission:incident_type_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:incident_type_create', ['only' => 'store']);
        $this->middleware('permission:incident_type_edit', ['only' => 'update']);
        $this->middleware('permission:incident_type_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(IncidentType::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                'name'
            ])
            ->allowedSorts([
                'id', 'company_id', 'name', 'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(IncidentType $incidentType)
    {
        $incidentType->load('company');
        return new DefaultResource($incidentType);
    }

    public function store(StoreRequest $request)
    {
        $incidentType = IncidentType::create($request->validated());

        return new DefaultResource($incidentType);
    }

    public function update(IncidentType $incidentType, StoreRequest $request)
    {
        $incidentType->update($request->validated());

        return (new DefaultResource($incidentType))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(IncidentType $incidentType)
    {
        $incidentType->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $incidentType = IncidentType::withTrashed()->findOrFail($id);
        $incidentType->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $incidentType = IncidentType::withTrashed()->findOrFail($id);
        $incidentType->restore();

        return new DefaultResource($incidentType);
    }
}
