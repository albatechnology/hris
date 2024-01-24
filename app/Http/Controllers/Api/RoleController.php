<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Role\StoreRequest;
use App\Http\Resources\Role\RoleResource;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:role_access', ['only' => ['index', 'show']]);
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
        $roles = QueryBuilder::for(Role::class)
            ->with('permissions')
            ->allowedFilters(['name'])
            ->allowedSorts(['id', 'name', 'created_at'])
            ->paginate($this->per_page);

        return RoleResource::collection($roles);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreRequest  $request
     * @return RoleResource
     */
    public function store(StoreRequest $request)
    {
        $permissionNames = PermissionService::getPermissionNames($request->permission_ids ?? []);
        $role = DB::transaction(function () use ($request, $permissionNames) {
            $role = new Role();
            $role->name = $request->name;
            $role->guard_name = 'web';
            $role->save();

            $role->syncPermissions($permissionNames ?? []);

            return $role;
        });

        cache()->flush();

        return new RoleResource($role);
    }
    /**
     * Display the specified resource.
     *
     * @param  Role  $role
     * @return RoleResource
     */
    public function show(Role $role)
    {
        return new RoleResource($role->load('permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Role  $role
     * @param  StoreRequest  $request
     * @return RoleResource
     */
    public function update(Role $role, StoreRequest $request)
    {
        if ($role->id == 1)
            return response()->json(['message' => 'Role admin tidak dapat diupdate!']);

        $permissionNames = PermissionService::getPermissionNames($request->permission_ids ?? []);
        $role = DB::transaction(function () use ($role, $request, $permissionNames) {
            $role->name = $request->name;
            $role->save();

            $role->syncPermissions($permissionNames ?? []);

            return $role;
        });

        cache()->flush();

        return new RoleResource($role);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        if ($role->id == 1)
            return response()->json(['message' => 'Role admin tidak dapat dihapus!']);
        $role->delete();
        return $this->deletedResponse();
    }
}
