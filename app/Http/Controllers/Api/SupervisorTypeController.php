<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SupervisorType\StoreRequest;
use App\Http\Resources\SupervisorType\SupervisorTypeResource;
use App\Models\SupervisorType;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SupervisorTypeController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:supervisor_type_access', ['only' => ['restore']]);
        $this->middleware('permission:supervisor_type_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:supervisor_type_create', ['only' => 'store']);
        $this->middleware('permission:supervisor_type_edit', ['only' => 'update']);
        // $this->middleware('permission:supervisor_type_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(SupervisorType::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'name', 'order', 'created_at',
            ])
            ->paginate($this->per_page);

        return SupervisorTypeResource::collection($data);
    }

    public function show(SupervisorType $supervisorType)
    {
        $data = QueryBuilder::for(SupervisorType::findTenanted($supervisorType->id))
            ->allowedIncludes(['company'])
            ->firstOrFail();

        return new SupervisorTypeResource($data);
    }

    public function store(StoreRequest $request)
    {
        $supervisorType = SupervisorType::create($request->validated());

        return new SupervisorTypeResource($supervisorType);
    }

    public function update(SupervisorType $supervisorType, StoreRequest $request)
    {
        $supervisorType->update($request->validated());

        return (new SupervisorTypeResource($supervisorType))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    // public function destroy(SupervisorType $supervisorType)
    // {
    //     $supervisorType->delete();

    //     return $this->deletedResponse();
    // }

    // public function forceDelete($id)
    // {
    //     $supervisorType = SupervisorType::withTrashed()->findOrFail($id);
    //     $supervisorType->forceDelete();

    //     return $this->deletedResponse();
    // }

    // public function restore($id)
    // {
    //     $supervisorType = SupervisorType::withTrashed()->findOrFail($id);
    //     $supervisorType->restore();

    //     return new SupervisorTypeResource($supervisorType);
    // }
}
