<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Enums\OvertimeRequestType;
use App\Http\Requests\Api\OvertimeRequest\StoreRequest;
use App\Http\Requests\Api\OvertimeRequest\ApproveRequest;
use App\Http\Resources\OvertimeRequest\OvertimeRequestResource;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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
        $data = QueryBuilder::for(OvertimeRequest::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                'approval_status', 'type'
            ])
            ->allowedSorts([
                'id', 'date',
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

        if ($request->type === OvertimeRequestType::OVERTIME->value) {
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
        }

        try {
            $overtimeRequest = OvertimeRequest::create($request->validated());

            $notificationType = NotificationType::REQUEST_OVERTIME;
            $overtimeRequest->user->approval?->notify(new ($notificationType->getNotificationClass())($notificationType, $overtimeRequest->user, $overtimeRequest));
        } catch (\Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeRequestResource($overtimeRequest);
    }

    public function approve(ApproveRequest $request, OvertimeRequest $overtimeRequest): OvertimeRequestResource|JsonResponse
    {
        try {
            $overtimeRequest->update($request->validated());

            $notificationType = NotificationType::OVERTIME_APPROVED;
            $overtimeRequest->user->notify(new ($notificationType->getNotificationClass())($notificationType, $overtimeRequest->approvedBy, $overtimeRequest->approval_status, $overtimeRequest));
        } catch (\Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeRequestResource($overtimeRequest);
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = DB::table('overtime_requests')->where('approved_by', auth('sanctum')->id())->where('approval_status', $request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = OvertimeRequest::whereHas('user', fn ($q) => $q->where('approval_id', auth('sanctum')->id()))
            ->with('user', fn ($q) => $q->select('id', 'name'));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'approval_status'
            ])
            ->allowedSorts([
                'id', 'date',
            ])
            ->paginate($this->per_page);

        return OvertimeRequestResource::collection($data);
    }
}
