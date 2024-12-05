<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Role\StoreRequest;
use App\Http\Resources\Role\RoleResource;
use App\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:role_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:role_create', ['only' => 'store']);
        $this->middleware('permission:role_edit', ['only' => 'update']);
        $this->middleware('permission:role_delete', ['only' => 'destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\ResourceCollection
     */
    public function index()
    {
        $roles = QueryBuilder::for(Role::tenanted())
            ->with('permissions')
            ->allowedFilters(['name', 'group_id'])
            ->allowedSorts(['id', 'name', 'group_id', 'created_at'])
            ->paginate($this->per_page);

        return RoleResource::collection($roles);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RoleResource
     */
    public function store(StoreRequest $request)
    {
        $permissionNames = $request->permission_ids ?? [];
        $role = DB::transaction(function () use ($request, $permissionNames) {
            $data = $request->validated();
            $data['guard_name'] = 'web';
            $role = Role::create($data);

            $role->syncPermissions($permissionNames ?? []);

            return $role;
        });

        Artisan::call('permission:cache-reset');

        return new RoleResource($role);
    }

    /**
     * Display the specified resource.
     *
     * @return RoleResource
     */
    public function show(int $id)
    {
        $role = Role::findTenanted($id);
        return new RoleResource($role->load('permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RoleResource
     */
    public function update(int $id, StoreRequest $request)
    {
        $role = Role::findTenanted($id);
        if ($role->id == 1) {
            return response()->json(['message' => 'Role administrator tidak dapat diupdate!']);
        }

        $permissionNames = $request->permission_ids ?? [];
        $role = DB::transaction(function () use ($request, $permissionNames, $role) {
            $data = $request->validated();
            $data['guard_name'] = 'web';
            $role->update($data);

            $role->syncPermissions($permissionNames ?? []);

            return $role;
        });

        Artisan::call('permission:cache-reset');

        return new RoleResource($role);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $role = Role::findTenanted($id);
        if ($role->id == 1) {
            return response()->json(['message' => 'Role administrator tidak dapat dihapus!']);
        }
        $role->delete();

        return $this->deletedResponse();
    }
}
