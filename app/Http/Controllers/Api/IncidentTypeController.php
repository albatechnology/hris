<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\IncidentType\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\IncidentType;
use Exception;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncidentTypeController extends BaseController
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
        $data = QueryBuilder::for(IncidentType::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'name'
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'created_at',
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
        try {
            $incidentType = IncidentType::create($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($incidentType);
    }

    public function update(IncidentType $incidentType, StoreRequest $request)
    {
        try {
            $incidentType->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($incidentType))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(IncidentType $incidentType)
    {
        try {
            $incidentType->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $incidentType = IncidentType::withTrashed()->findOrFail($id);

        try {
            $incidentType->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $incidentType = IncidentType::withTrashed()->findOrFail($id);

        try {
            $incidentType->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($incidentType);
    }
}
