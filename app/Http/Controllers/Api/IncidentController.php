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
        $data = QueryBuilder::for(Incident::tenanted()->with('media'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('incident_type_id'),
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'user_id',
                'incident_type_id',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(Incident $incident)
    {
        $incident->load(['user', 'incidentType', 'media']);
        return new DefaultResource($incident);
    }

    public function store(StoreRequest $request)
    {
        try {
            $incident = auth('sanctum')->user()->incidents()->create([
                'company_id' => $request->company_id,
                'incident_type_id' => $request->incident_type_id,
                'description' => $request->description,
            ]);

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

    public function update(Incident $incident, StoreRequest $request)
    {
        try {
            $incident->update([
                'company_id' => $request->company_id,
                'incident_type_id' => $request->incident_type_id,
                'description' => $request->description,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($incident))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Incident $incident)
    {
        try {
            $incident->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $incident = Incident::withTrashed()->findOrFail($id);

        try {
            $incident->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $incident = Incident::withTrashed()->findOrFail($id);

        try {
            $incident->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($incident);
    }
}
