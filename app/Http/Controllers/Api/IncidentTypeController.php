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
        $datas = QueryBuilder::for(IncidentType::tenanted())
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

        return DefaultResource::collection($datas);
    }

    public function show(string $id)
    {
        $data = IncidentType::findTenanted($id);
        $data->load('company');
        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        IncidentType::create($request->validated());

        return $this->createdResponse();
    }

    public function update(string $id, StoreRequest $request)
    {
        $data = IncidentType::findTenanted($id);

        $data->update($request->validated());

        return $this->updatedResponse();
    }

    public function destroy(string $id)
    {
        $data = IncidentType::findTenanted($id);
        $data->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(string $id)
    {
        $data = IncidentType::withTrashed()->tenanted()->where('id', $id)->firstOrFail();

        $data->forceDelete();

        return $this->forceDeletedResponse();
    }

    public function restore(string $id)
    {
        $data = IncidentType::withTrashed()->tenanted()->where('id', $id)->firstOrFail();

        $data->restore();

        return $this->restoredResponse();
    }
}
