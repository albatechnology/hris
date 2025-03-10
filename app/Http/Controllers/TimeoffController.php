<?php

namespace App\Http\Controllers;

use App\Http\Requests\Timeoff\MassDestroyRequest;
use App\Http\Requests\Timeoff\StoreRequest;
use App\Http\Requests\Timeoff\UpdateRequest;
use App\Models\Timeoff;
use App\View\Components\Datatables\DatatableAction;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use DataTables;

class TimeoffController extends Controller
{
    // constructor
    public function __construct(public Timeoff $model)
    {
        $this->middleware('permission:timeoff_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:timeoff_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:timeoff_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:timeoff_delete', ['only' => ['destroy', 'massDestroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $query = $this->model->with('user', 'timeoffPolicy');
            $table = DataTables::of($query);

            $table->addColumn('checkbox', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                return Blade::renderComponent(new DatatableAction(
                    showRoute: route('timeoffs.show', $row->id),
                    editRoute: null,
                    destroyRoute: route('timeoffs.destroy', $row->id),
                    access: 'timeoff_access',
                ));
            });

            $table->rawColumns(['checkbox', 'actions']);

            return $table->make(true);
        }

        return view('timeoffs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('timeoffs.create', [
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

        return to_route('timeoffs.index')->with(key($alert), current($alert));
    }

    /**
     * Display the specified resource.
     */
    public function show(Timeoff $timeoff): View
    {
        return view('timeoffs.show', [
            'model' => $timeoff,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Timeoff $timeoff)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Timeoff $timeoff)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Timeoff $timeoff): RedirectResponse
    {
        try {
            $timeoff->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('timeoffs.index')->with(key($alert), current($alert));
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
