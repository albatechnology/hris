<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\MassDestroyRequest;
use App\Http\Requests\Role\StoreRequest;
use App\Http\Requests\Role\UpdateRequest;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Blade;
use App\View\Components\Datatables\DatatableAction;
use DataTables;
use Exception;

class RoleController extends Controller
{
    public function __construct(public Role $model)
    {
        $this->middleware('permission:role_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:role_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:role_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:role_delete', ['only' => ['destroy', 'massDestroy']]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->model->select(sprintf('%s.*', (new $this->model)->getTable()))->with('group');
            $table = DataTables::of($query);

            $table->addColumn('checkbox', '&nbsp;');
            $table->addColumn('group_name', fn($q) => $q->group?->name ?? '');

            $table->editColumn('actions', function ($row) {
                return Blade::renderComponent(new DatatableAction(
                    editRoute: route('roles.edit', $row->id),
                    destroyRoute: route('roles.destroy', $row->id),
                    access: 'role_access',
                ));
            });

            $table->rawColumns(['checkbox', 'actions']);

            return $table->make(true);
        }

        return view('roles.index');
    }

    public function create(): View
    {
        $groups = Group::get(['id', 'name'])->pluck('name', 'id')->prepend('- Select Group -', null);
        $permissions = Permission::whereNull('parent_id')->get();

        return view('roles.create', [
            'model' => $this->model,
            'groups' => $groups,
            'permissions' => $permissions,
        ]);
    }

    public function store(StoreRequest $request)
    {
        try {
            $role = Role::create($request->validated());
            $role->syncPermissions(array_map('intval', $request->permission_ids ?? []));

            $alert['success'] = self::CREATED_MESSAGE;
        } catch (\Exception $th) {
            $alert['error'] = $th->getMessage();
        }

        return to_route('roles.index')->with(key($alert), current($alert));
    }

    public function edit(Role $role)
    {
        $rolePermissions = $role->permissions->pluck('id')->all();
        $groups = Group::get(['id', 'name'])->pluck('name', 'id')->prepend('- Select Group -', null);
        $permissions = Permission::whereNull('parent_id')->get();

        return view('roles.edit', [
            'model' => $role,
            'groups' => $groups,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions
        ]);
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->update($request->all());
            $role->syncPermissions(array_map('intval', $request->permission_ids ?? []));

            $alert['success'] = self::UPDATED_MESSAGE;
        } catch (\Exception $th) {
            $alert['error'] = $th->getMessage();
        }

        return to_route('roles.index')->with(key($alert), current($alert));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        try {
            $role->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('roles.index')->with(key($alert), current($alert));
    }

    /**
     * Remove the mass resource from storage.
     */
    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        try {
            $this->model->whereIn('id', $request->ids)->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return $this->jsonResponse(key($alert), current($alert));
    }
}
