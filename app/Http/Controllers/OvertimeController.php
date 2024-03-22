<?php

namespace App\Http\Controllers;

use App\Http\Requests\Overtime\MassDestroyRequest;
use App\Http\Requests\Overtime\StoreRequest;
use App\Http\Requests\Overtime\UpdateRequest;
use App\Models\Overtime;
use App\View\Components\Datatables\DatatableAction;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use DataTables;

class OvertimeController extends Controller
{
    // constructor
    public function __construct(public Overtime $model)
    {
        $this->middleware('permission:overtime_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:overtime_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:overtime_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:overtime_delete', ['only' => ['destroy', 'massDestroy']]);
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
                    showRoute: route('overtimes.show', $row->id),
                    editRoute: null,
                    destroyRoute: route('overtimes.destroy', $row->id),
                    access: 'overtime_access',
                ));
            });

            $table->rawColumns(['checkbox', 'actions']);

            return $table->make(true);
        }

        return view('overtimes.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('overtimes.create', [
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

        return to_route('overtimes.index')->with(key($alert), current($alert));
    }

    /**
     * Display the specified resource.
     */
    public function show(Overtime $overtime): View
    {
        return view('overtimes.show', [
            'model' => $overtime,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Overtime $overtime)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Overtime $overtime)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Overtime $overtime): RedirectResponse
    {
        try {
            $overtime->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('overtimes.index')->with(key($alert), current($alert));
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
