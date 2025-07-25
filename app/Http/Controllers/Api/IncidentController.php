<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Incident\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Incident;
use Exception;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Enums\MediaCollection;
use App\Http\Requests\Api\Incident\ExportRequest;

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
        $query = Incident::tenanted()->with(['user' => fn($q) => $q->selectMinimalist()->with(
            'branch',
            fn($q) => $q->selectMinimalist()
        ), 'incidentType', 'media']);

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('incident_type_id'),
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'branch_id',
                'user_id',
                'incident_type_id',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $incident = Incident::findTenanted($id);
        $incident->load(['user' => fn($q) => $q->selectMinimalist()->with('branch', fn($q) => $q->selectMinimalist()), 'incidentType', 'media']);
        return new DefaultResource($incident);
    }

    public function store(StoreRequest $request)
    {
        try {
            $incident = auth('sanctum')->user()->incidents()->create($request->validated());

            if ($request->hasFile('file')) {
                foreach ($request->file('file') as $file) {
                    if ($file->isValid()) {
                        $incident->addMedia($file)->toMediaCollection(MediaCollection::DEFAULT->value);
                    }
                }
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($incident);
    }

    public function update(int $id, StoreRequest $request)
    {
        $incident = Incident::findTenanted($id);
        try {
            $incident->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($incident))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $incident = Incident::findTenanted($id);
        try {
            $incident->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $incident = Incident::withTrashed()->tenanted()->where('id', $id)->firstOrFail();

        try {
            $incident->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $incident = Incident::withTrashed()->tenanted()->where('id', $id)->firstOrFail();

        try {
            $incident->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($incident);
    }

    public function export(ExportRequest $request)
    {
        return (new \App\Exports\Incident\ExportIncident($request))->download('incidents.xlsx');
    }
}
