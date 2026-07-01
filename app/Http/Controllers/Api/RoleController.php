<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Role\StoreRequest;
use App\Http\Resources\Role\RoleResource;
use App\Interfaces\Services\Role\RoleServiceInterface;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends BaseController
{
    public function __construct(private RoleServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        Gate::authorize('viewAny', Role::class);

        $roles = QueryBuilder::for(Role::tenanted())
            ->with('permissions')
            ->allowedFilters(['name', 'group_id'])
            ->allowedSorts(['id', 'name', 'group_id', 'created_at'])
            ->paginate($this->per_page);

        return RoleResource::collection($roles);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Role::class);

        $role = $this->service->create($request->validated());

        return new RoleResource($role);
    }

    public function show(int $id)
    {
        $role = $this->service->findById($id);
        Gate::authorize('view', $role);

        return new RoleResource($role->load('permissions'));
    }

    public function update(int $id, StoreRequest $request)
    {
        $role = $this->service->findById($id);
        Gate::authorize('update', $role);

        $role = $this->service->update($id, $request->validated());

        return new RoleResource($role);
    }

    public function destroy(int $id)
    {
        $role = $this->service->findById($id);
        Gate::authorize('delete', $role);

        $this->service->delete($id);

        return $this->deletedResponse();
    }
}
