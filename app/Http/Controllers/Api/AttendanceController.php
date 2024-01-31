<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Attendance\ClockInRequest;
use App\Http\Requests\Api\Attendance\ClockOutRequest;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Models\Attendance;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AttendanceController extends BaseController
{
    const ALLOWED_INCLUDES = ['user', 'schedule', 'shift'];

    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:attendance_access', ['only' => ['index', 'show', 'restore']]);
        // $this->middleware('permission:attendance_access', ['only' => ['restore']]);
        // $this->middleware('permission:attendance_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:attendance_create', ['only' => 'store']);
        // $this->middleware('permission:attendance_edit', ['only' => 'update']);
        // $this->middleware('permission:attendance_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $attendances = QueryBuilder::for(Attendance::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('schedule_id'),
                AllowedFilter::exact('shift_id'),
                AllowedFilter::scope('company_id', 'whereCompanyId'),
                AllowedFilter::scope('shift_id', 'whereShiftId'),
                'is_clock_in', 'time', 'type', 'is_approved', 'approved_by'
            ])
            ->allowedIncludes(self::ALLOWED_INCLUDES)
            ->allowedSorts([
                'id', 'user_id', 'schedule_id', 'shift_id', 'is_clock_in', 'time', 'type', 'is_approved', 'approved_by', 'created_at'
            ])
            ->paginate($this->per_page);

        return AttendanceResource::collection($attendances);
    }

    public function show(Attendance $attendance)
    {
        $attendance = QueryBuilder::for(Attendance::where('id', $attendance->id))
            ->allowedIncludes(self::ALLOWED_INCLUDES)
            ->firstOrFail();

        return new AttendanceResource($attendance);
    }

    public function clockIn(ClockInRequest $request)
    {
        $attendance = Attendance::create($request->validated());

        return new AttendanceResource($attendance);
    }

    public function clockOut(ClockOutRequest $request)
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        $attendance = $user->attendances()->whereDate('time', date('Y-m-d'))->first();
        if (!$attendance) return $this->errorResponse(message: "No attendance today", code: 404);
        $attendance = Attendance::create($request->validated());

        return new AttendanceResource($attendance);
    }

    public function destroy(Attendance $attendance)
    {
        if ($attendance->id == 1) return response()->json(['message' => 'Admin dengan id 1 tidak dapat dihapus!']);
        // abort_if(!auth()->Attendance()->tokenCan('attendance_delete'), 403);
        $attendance->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        if ($id == 1) return response()->json(['message' => 'Admin dengan id 1 tidak dapat dihapus!']);
        // abort_if(!auth()->Attendance()->tokenCan('attendance_delete'), 403);
        $attendance = Attendance::withTrashed()->findOrFail($id);
        $attendance->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        // abort_if(!auth()->Attendance()->tokenCan('attendance_access'), 403);
        $attendance = Attendance::withTrashed()->findOrFail($id);
        $attendance->restore();
        return new AttendanceResource($attendance);
    }
}
