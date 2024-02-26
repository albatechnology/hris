<?php

namespace App\Http\Controllers;

use App\Http\Requests\Branch\MassDestroyRequest;
use App\Http\Requests\Branch\StoreRequest;
use App\Http\Requests\Branch\UpdateRequest;
use App\Models\Branch;
use App\View\Components\Datatables\DatatableAction;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use DataTables;

class BranchController extends Controller
{
    // constructor
    public function __construct(public Branch $model)
    {
        $this->middleware('permission:branch_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:branch_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:branch_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:branch_delete', ['only' => ['destroy', 'massDestroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $query = $this->model->with('company');
            $table = DataTables::of($query);

            $table->addColumn('checkbox', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                return Blade::renderComponent(new DatatableAction(
                    showRoute: route('branches.show', $row->id),
                    editRoute: route('branches.edit', $row->id),
                    destroyRoute: route('branches.destroy', $row->id),
                    access: 'branch_access',
                ));
            });

            $table->rawColumns(['checkbox', 'actions']);

            return $table->make(true);
        }

        return view('branches.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('branches.create', [
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

        return to_route('branches.index')->with(key($alert), current($alert));
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch): View
    {
        return view('branches.show', [
            'model' => $branch,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch): View
    {
        return view('branches.edit', [
            'model' => $branch,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Branch $branch)
    {
        try {
            $branch->update($request->validated());

            $alert['success'] = self::UPDATED_MESSAGE;
        } catch (\Exception $th) {
            $alert['error'] = $th->getMessage();
        }

        return to_route('branches.index')->with(key($alert), current($alert));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        try {
            $branch->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('branches.index')->with(key($alert), current($alert));
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
