<?php

namespace App\Http\Controllers;

use App\Http\Requests\NationalHoliday\MassDestroyRequest;
use App\Http\Requests\NationalHoliday\StoreRequest;
use App\Http\Requests\NationalHoliday\UpdateRequest;
use App\Models\NationalHoliday;
use App\View\Components\Datatables\DatatableAction;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use DataTables;

class NationalHolidayController extends Controller
{
    // constructor
    public function __construct(public NationalHoliday $model)
    {
        $this->middleware('permission:national_holiday_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:national_holiday_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:national_holiday_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:national_holiday_delete', ['only' => ['destroy', 'massDestroy']]);
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
                    showRoute: route('national-holidays.show', $row->id),
                    editRoute: route('national-holidays.edit', $row->id),
                    destroyRoute: route('national-holidays.destroy', $row->id),
                    access: 'national_holiday_access',
                ));
            });

            $table->rawColumns(['checkbox', 'actions']);

            return $table->make(true);
        }

        return view('national-holidays.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('national-holidays.create', [
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

        return to_route('national-holidays.index')->with(key($alert), current($alert));
    }

    /**
     * Display the specified resource.
     */
    public function show(NationalHoliday $nationalHoliday): View
    {
        return view('national-holidays.show', [
            'model' => $nationalHoliday,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NationalHoliday $nationalHoliday): View
    {
        return view('national-holidays.edit', [
            'model' => $nationalHoliday,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NationalHoliday $nationalHoliday, UpdateRequest $request)
    {
        try {
            $nationalHoliday->update($request->validated());

            $alert['success'] = self::UPDATED_MESSAGE;
        } catch (\Exception $th) {
            $alert['error'] = $th->getMessage();
        }

        return to_route('national-holidays.index')->with(key($alert), current($alert));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NationalHoliday $nationalHoliday): RedirectResponse
    {
        try {
            $nationalHoliday->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('national-holidays.index')->with(key($alert), current($alert));
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
