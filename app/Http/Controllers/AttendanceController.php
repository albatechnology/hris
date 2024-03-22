<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceType;
use App\Enums\MediaCollection;
use App\Events\Attendance\AttendanceRequested;
use App\Http\Requests\Attendance\MassDestroyRequest;
use App\Http\Requests\Attendance\StoreRequest;
use App\Http\Requests\Attendance\UpdateRequest;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Services\AttendanceService;
use App\View\Components\Datatables\DatatableAction;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use DataTables;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    // constructor
    public function __construct(public Attendance $model)
    {
        $this->middleware('permission:attendance_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:attendance_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:attendance_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:attendance_delete', ['only' => ['destroy', 'massDestroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $query = $this->model->with('user', 'clockIn', 'clockOut', 'schedule', 'shift');
            $table = DataTables::of($query);

            $table->addColumn('checkbox', '&nbsp;');

            $table->addColumn('clock_in', fn ($row) => $row->clockIn?->time);
            $table->addColumn('clock_out', fn ($row) => $row->clockOut?->time);

            $table->editColumn('actions', function ($row) {
                return Blade::renderComponent(new DatatableAction(
                    showRoute: route('attendances.show', $row->id),
                    editRoute: null,
                    destroyRoute: route('attendances.destroy', $row->id),
                    access: 'attendance_access',
                ));
            });

            $table->rawColumns(['checkbox', 'actions']);

            return $table->make(true);
        }

        return view('attendances.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('attendances.create', [
            'model' => $this->model,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        $attendance = AttendanceService::getTodayAttendance($request->schedule_id, $request->shift_id, auth('sanctum')->user(), $request->time);

        DB::beginTransaction();
        try {
            if (!$attendance) {
                $data = [
                    'date' => date('Y-m-d', strtotime($request->time)),
                    ...$request->validated(),
                ];
                $attendance = Attendance::create($data);
            }

            /** @var AttendanceDetail $attendanceDetail */
            $attendanceDetail = $attendance->details()->create($request->validated());

            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $mediaCollection = MediaCollection::ATTENDANCE->value;
                $attendanceDetail->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }

            AttendanceRequested::dispatchIf($attendanceDetail->type->is(AttendanceType::MANUAL), $attendance);
            DB::commit();
            $alert['success'] = self::CREATED_MESSAGE;
        } catch (\Exception $e) {
            DB::rollBack();
            $alert['error'] = $e->getMessage();
        }

        return to_route('attendances.index')->with(key($alert), current($alert));
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance): View
    {
        $attendance->load([
            'details',
        ]);

        return view('attendances.show', [
            'model' => $attendance,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Attendance $attendance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance): RedirectResponse
    {
        try {
            $attendance->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('attendances.index')->with(key($alert), current($alert));
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
