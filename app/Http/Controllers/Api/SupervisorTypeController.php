<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SupervisorType\StoreRequest;
use App\Http\Resources\SupervisorType\SupervisorTypeResource;
use App\Interfaces\Services\SupervisorType\SupervisorTypeServiceInterface;
use App\Models\SupervisorType;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class SupervisorTypeController extends BaseController
{
    public function __construct(private SupervisorTypeServiceInterface $service)
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
        Gate::authorize('viewAny', SupervisorType::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            null,
            [
                AllowedFilter::exact('company_id'),
            ],
            ['company'],
            [
                'id',
                'company_id',
                'name',
                'order',
                'created_at',
            ],
        );

        return SupervisorTypeResource::collection($datas);
    }

    public function show(int $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        $data->load(['company']);

        return new SupervisorTypeResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', SupervisorType::class);

        $data = $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(int $id, StoreRequest $request)
    {
        $data = $this->service->findById($id);
        Gate::authorize('update', $data);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    // public function destroy(int $id)
    // {
    // $data = SupervisorType::findTenanted($id);
    //     $data->delete();

    //     return $this->deletedResponse();
    // }

    // public function forceDelete($id)
    // {
    //     $data = SupervisorType::withTrashed()->findOrFail($id);
    //     $data->forceDelete();

    //     return $this->deletedResponse();
    // }

    // public function restore($id)
    // {
    //     $data = SupervisorType::withTrashed()->findOrFail($id);
    //     $data->restore();

    //     return new SupervisorTypeResource($data);
    // }
}
