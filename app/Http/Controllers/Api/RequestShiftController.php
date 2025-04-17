<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Http\Requests\Api\NewApproveRequest;
use App\Http\Requests\Api\RequestShift\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Attendance;
use App\Models\PayrollSetting;
use App\Models\RequestShift;
use App\Models\Shift;
use App\Models\User;
use App\Services\ScheduleService;
use App\Services\ShiftService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RequestShiftController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        // $this->middleware('permission:request_shift_access', ['only' => ['restore']]);
        // $this->middleware('permission:request_shift_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:request_shift_create', ['only' => 'store']);
        // $this->middleware('permission:request_shift_edit', ['only' => 'update']);
        // $this->middleware('permission:request_shift_delete', ['only' => 'destroy']);
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(
            RequestShift::tenanted()->with('approvals', fn($q) => $q->with('user', fn($q) => $q->select('id', 'name')))
                ->with([
                    'user' => fn($q) => $q->select('id', 'name'),
                    'oldShift' => fn($q) => $q->selectMinimalist(),
                    'newShift' => fn($q) => $q->selectMinimalist(),
                ])
        )
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('schedule_id'),
                AllowedFilter::exact('old_shift_id'),
                AllowedFilter::exact('new_shift_id'),
                'date',
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'schedule_id',
                'old_shift_id',
                'new_shift_id',
                'date',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id): DefaultResource
    {
        $requestShift = RequestShift::with([
            'user' => fn($q) => $q->select('id', 'name'),
            'oldShift' => fn($q) => $q->selectMinimalist(),
            'newShift' => fn($q) => $q->selectMinimalist(),
        ])->findTenanted($id);

        return new DefaultResource($requestShift);
    }

    public function store(StoreRequest $request): DefaultResource|JsonResponse
    {
        $data = $request->validated();
        $user = User::select('id', 'company_id', 'branch_id')->findOrFail($request->user_id);

        // 1. check if user has schedule on date requested
        // 2. check if shift requested is part of schedule
        // 3. check if on date requested, attendance exist
        $schedule = ScheduleService::getTodaySchedule($user, $request->date);
        if (!$schedule || !$schedule?->shift) {
            return $this->errorResponse(message: 'Schedule not found at ' . $request->date, code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data['schedule_id'] = $schedule->id;
        $data['old_shift_id'] = $schedule->shift->id;

        // turn of this check, because user can request shift that is is_show_in_request = true and eligible for taken
        // $schedule->load(['shift' => fn($q) => $q->where('id', $request->new_shift_id)]);
        // if (!$schedule?->shift) {
        //     return $this->errorResponse(message: 'Shift requested not found', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        // }

        if (!ShiftService::validateRequestShift($request->new_shift_id, $user)) {
            return $this->errorResponse(message: 'Shift requested not found', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payrollSetting = PayrollSetting::where('company_id', $user->company_id)->firstOrFail(['cut_off_attendance_start_date']);

        $cutOffAttendanceStartDate = Carbon::createFromDate(date('Y'), date('m'), $payrollSetting->cut_off_attendance_start_date)->subMonth()->startOfDay();
        // $cutOffAttendanceEndDate = Carbon::createFromDate(date('Y'), date('m'), $payrollSetting->cut_off_attendance_end_date)->subMonth()->startOfDay();

        $now = Carbon::now()->startOfDay();
        $requestDate = Carbon::parse($request->date)->startOfDay();

        $attendance = Attendance::where('user_id', $request->user_id)->whereDate('date', $request->date)->exists();
        if ($requestDate->lessThan($now)) {
            if (($requestDate->greaterThanOrEqualTo($cutOffAttendanceStartDate) && $requestDate->lessThanOrEqualTo($now)) === false) {
                return $this->errorResponse(message: "Request change shift only available for {$cutOffAttendanceStartDate->format('d-m-Y')} to {$now->format('d-m-Y')}", code: Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (!$attendance && Shift::where('id', $request->new_shift_id)->where('is_dayoff', true)->doesntExist()) {
                return $this->errorResponse(message: 'Attendance not found for ' . $request->date, code: Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $data['is_for_replace'] = true;
        } elseif ($requestDate->equalTo($now)) {
            $data['is_for_replace'] = true;
        }
        // else {
        //     if ($attendance) {
        //         return $this->errorResponse(message: 'Attendance already exist for ' . $request->date, code: Response::HTTP_UNPROCESSABLE_ENTITY);
        //     }
        // }

        DB::beginTransaction();
        try {
            $requestShift = RequestShift::create($data);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($requestShift);
    }

    public function destroy(int $id)
    {
        $requestShift = RequestShift::findTenanted($id);
        if (!$requestShift->approval_status->is(ApprovalStatus::PENDING)) return $this->errorResponse(message: 'Cannot delete pending overtime request', code: 422);

        $requestShift->delete();
        return $this->deletedResponse();
    }

    public function approve(NewApproveRequest $request, int $id): DefaultResource|JsonResponse
    {
        $requestShift = RequestShift::findTenanted($id);
        $requestApproval = $requestShift->approvals()->where('user_id', auth()->id())->first();

        if (!$requestApproval) return $this->errorResponse(message: 'You are not registered as approved', code: Response::HTTP_NOT_FOUND);

        if ($requestShift->approval_status == ApprovalStatus::REJECTED->value) return $this->errorResponse(message: 'Request has been rejected', code: Response::HTTP_UNPROCESSABLE_ENTITY);

        if (!$requestShift->isDescendantApproved()) return $this->errorResponse(message: 'You have to wait for your subordinates to approve', code: Response::HTTP_UNPROCESSABLE_ENTITY);

        if ($requestShift->approval_status == ApprovalStatus::APPROVED->value || $requestApproval->approval_status->in([ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])) {
            return $this->errorResponse(message: 'Status can not be changed', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $requestApproval->update($request->validated());
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($requestShift);
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = RequestShift::myApprovals()
            ->whereApprovalStatus($request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = RequestShift::myApprovals()
            ->with([
                'user' => fn($q) => $q->select('id', 'name'),
                'oldShift' => fn($q) => $q->selectMinimalist(),
                'newShift' => fn($q) => $q->selectMinimalist(),
            ]);

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('schedule_id'),
                AllowedFilter::exact('old_shift_id'),
                AllowedFilter::exact('new_shift_id'),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                'date',
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'schedule_id',
                'old_shift_id',
                'new_shift_id',
                'date',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function availableShifts()
    {
        /** @var User $user */
        $user = auth()->user()?->load('positions');

        $branchId = $user->branch_id;
        $departmentIds = $user->positions->pluck('department_id')?->toArray();
        $positionIds = $user->positions->pluck('position_id')?->toArray();

        $schedule = ScheduleService::getTodaySchedule(scheduleColumn: ['id'], shiftColumn: ['id']);

        $shifts = Shift::tenanted()
            ->where(
                fn($q) =>
                $q->when($schedule, fn($q) => $q->whereHas('schedules', fn($q) => $q->where('schedule_id', $schedule->id)))
                    ->orWhere(function ($q) use ($branchId, $departmentIds, $positionIds) {
                        $q->where('is_show_in_request', true)->where('is_show_in_request_for_all', true)
                            ->orWhere(function ($q) use ($branchId) {
                                $q->where('is_show_in_request', true)->where('is_show_in_request_for_all', false)->whereRaw('JSON_CONTAINS(show_in_request_branch_ids, ?)', json_encode($branchId));
                            })
                            ->orWhere(function ($q) use ($departmentIds) {
                                $q->where('is_show_in_request', true)->where('is_show_in_request_for_all', false)->whereRaw('JSON_CONTAINS(show_in_request_department_ids, ?)', json_encode($departmentIds));
                            })
                            ->orWhere(function ($q) use ($positionIds) {
                                $q->where('is_show_in_request', true)->where('is_show_in_request_for_all', false)->whereRaw('JSON_CONTAINS(show_in_request_position_ids, ?)', json_encode($positionIds));
                            });
                    })
            )
            ->orWhereNull('company_id')
            ->get(['id', 'type', 'is_dayoff', 'name', 'clock_in', 'clock_out']);

        return DefaultResource::collection($shifts);
    }
}
