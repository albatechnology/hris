<?php

namespace App\Http\Controllers;

use App\Http\Requests\Group\MassDestroyRequest;
use App\Http\Requests\Group\StoreRequest;
use App\Http\Requests\Group\UpdateRequest;
use App\Models\Group;
use App\View\Components\Datatables\DatatableAction;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use DataTables;

class GroupController extends Controller
{
    // constructor
    public function __construct(public Group $model)
    {
        $this->middleware('permission:group_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:group_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:group_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:group_delete', ['only' => ['destroy', 'massDestroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $query = $this->model->query();
            $table = DataTables::of($query);

            $table->addColumn('checkbox', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                return Blade::renderComponent(new DatatableAction(
                    showRoute: route('groups.show', $row->id),
                    editRoute: route('groups.edit', $row->id),
                    destroyRoute: route('groups.destroy', $row->id),
                    access: 'group_access',
                ));
            });

            $table->rawColumns(['checkbox', 'actions']);

            return $table->make(true);
        }

        return view('groups.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('groups.create', [
            'model' => $this->model,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->model->create($request->validated());

            $alert['success'] = self::CREATED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('groups.index')->with(key($alert), current($alert));
    }

    /**
     * Display the specified resource.
     */
    public function show(Group $group): View
    {
        return view('groups.show', [
            'model' => $group,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Group $group): View
    {
        return view('groups.edit', [
            'model' => $group,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Group $group)
    {
        try {
            $group->update($request->validated());

            $alert['success'] = self::UPDATED_MESSAGE;
        } catch (\Exception $th) {
            $alert['error'] = $th->getMessage();
        }

        return to_route('groups.index')->with(key($alert), current($alert));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group): RedirectResponse
    {
        try {
            $group->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('groups.index')->with(key($alert), current($alert));
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
