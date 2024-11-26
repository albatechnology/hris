<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Http\Requests\Api\NewApproveRequest;
use App\Http\Requests\Api\OvertimeRequest\StoreRequest;
use App\Http\Resources\OvertimeRequest\OvertimeRequestResource;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OvertimeRequestController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:overtime_request_access', ['only' => ['restore']]);
        $this->middleware('permission:overtime_request_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:overtime_request_create', ['only' => 'store']);
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(
            OvertimeRequest::tenanted()->with('approvals', fn($q) => $q->with('user', fn($q) => $q->select('id', 'name')))
                ->with([
                    'user' => fn($q) => $q->select('id', 'name'),
                    'shift' => fn($q) => $q->select('id', 'is_dayoff', 'name', 'clock_in', 'clock_out'),
                ])
        )
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('shift_id'),
                'date',
                'is_after_shift'
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'shift_id',
                'date',
            ])
            ->paginate($this->per_page);

        return OvertimeRequestResource::collection($data);
    }

    public function show(OvertimeRequest $overtimeRequest): OvertimeRequestResource
    {
        return new OvertimeRequestResource($overtimeRequest);
    }

    public function store(StoreRequest $request): OvertimeRequestResource|JsonResponse
    {
        $user = User::findOrFail($request->user_id);

        $attendance = AttendanceService::getTodayAttendance($request->schedule_id, $request->shift_id, $user, $request->date);
        if (!$attendance) {
            return $this->errorResponse(message: 'Attendance not found at ' . $request->date, code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($attendance->clockIn()->doesntExist()) {
            return $this->errorResponse(message: 'Attendance clock in not found at ' . $request->date, code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($attendance->clockOut()->doesntExist()) {
            return $this->errorResponse(message: 'Attendance clock out not found at ' . $request->date, code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $overtimeRequest = OvertimeRequest::create($request->validated());

            // $notificationType = NotificationType::REQUEST_OVERTIME;
            // $overtimeRequest->user->approval?->notify(new ($notificationType->getNotificationClass())($notificationType, $overtimeRequest->user, $overtimeRequest));
        } catch (\Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeRequestResource($overtimeRequest);
    }

    public function destroy(int $id)
    {
        $overtimeRequest = OvertimeRequest::findTenanted($id);
        if (!$overtimeRequest->approval_status->is(ApprovalStatus::PENDING)) return $this->errorResponse(message: 'Cannot delete pending overtime request', code: 422);

        $overtimeRequest->delete();
        return $this->deletedResponse();
    }

    public function approve(NewApproveRequest $request, OvertimeRequest $overtimeRequest): OvertimeRequestResource|JsonResponse
    {
        $requestApproval = $overtimeRequest->approvals()->where('user_id', auth()->id())->first();

        if (!$requestApproval) return $this->errorResponse(message: 'You are not registered as approved', code: Response::HTTP_NOT_FOUND);

        if (!$overtimeRequest->isDescendantApproved()) return $this->errorResponse(message: 'You have to wait for your subordinates to approve', code: Response::HTTP_UNPROCESSABLE_ENTITY);

        if ($overtimeRequest->approval_status == ApprovalStatus::APPROVED->value || $requestApproval->approval_status->in([ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])) {
            return $this->errorResponse(message: 'Status can not be changed', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $requestApproval->update($request->validated());

            // $notificationType = NotificationType::OVERTIME_APPROVED;
            // $overtimeRequest->user->notify(new ($notificationType->getNotificationClass())($notificationType, $overtimeRequest->approvedBy, $overtimeRequest->approval_status, $overtimeRequest));
        } catch (\Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeRequestResource($overtimeRequest);
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = OvertimeRequest::myApprovals()
            ->whereApprovalStatus($request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = OvertimeRequest::myApprovals()
            ->with([
                'user' => fn($q) => $q->select('id', 'name'),
                // 'approvedBy' => fn($q) => $q->select('id', 'name'),
                'shift' => fn($q) => $q->select('id', 'is_dayoff', 'name', 'clock_in', 'clock_out'),
                'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))
            ]);

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('shift_id'),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                'date',
                'is_after_shift'
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'shift_id',
                // 'approval_status',
                'date',
            ])
            ->paginate($this->per_page);

        return OvertimeRequestResource::collection($data);
    }
}
