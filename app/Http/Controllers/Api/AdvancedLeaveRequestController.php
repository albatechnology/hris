<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\NotificationType;
use App\Http\Requests\Api\AdvancedLeaveRequest\StoreRequest;
use App\Http\Requests\Api\ApproveRequest;
use App\Http\Resources\AdvancedLeaveRequest\AdvancedLeaveRequestResource;
use App\Models\AdvancedLeaveRequest;
use App\Models\User;
use App\Services\AdvancedLeaveRequestService;
use App\Services\AttendanceService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AdvancedLeaveRequestController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        // $this->middleware('permission:advanced_leave_request_access', ['only' => ['restore']]);
        // $this->middleware('permission:advanced_leave_request_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:advanced_leave_request_create', ['only' => 'store']);
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(AdvancedLeaveRequest::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                'approval_status'
            ])
            ->allowedSorts([
                'id',
                'date',
            ])
            ->paginate($this->per_page);

        return AdvancedLeaveRequestResource::collection($data);
    }

    public function show(AdvancedLeaveRequest $advancedLeaveRequest): AdvancedLeaveRequestResource
    {
        return new AdvancedLeaveRequestResource($advancedLeaveRequest);
    }

    public function store(StoreRequest $request): AdvancedLeaveRequestResource|JsonResponse
    {
        $user = User::findOrFail($request->user_id);

        if (AttendanceService::inLockAttendance($request->time, $user)) {
            throw new UnprocessableEntityHttpException('Attendance is locked');
        }

        $availableDays = AdvancedLeaveRequestService::getAvailableDays($user);
        if ($request->amount > $availableDays) {
            $message = $availableDays == 0 ? 'You have no available days' : 'Request days exceeds available days';
            return $this->errorResponse(message: $message, code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $advancedLeaveRequest = AdvancedLeaveRequest::create($request->validated());

            $notificationType = NotificationType::REQUEST_ADVANCED_LEAVE;
            $advancedLeaveRequest->user->approval?->notify(new ($notificationType->getNotificationClass())($notificationType, $advancedLeaveRequest->user, $advancedLeaveRequest));
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new AdvancedLeaveRequestResource($advancedLeaveRequest);
    }

    public function approve(ApproveRequest $request, AdvancedLeaveRequest $advancedLeaveRequest): AdvancedLeaveRequestResource|JsonResponse
    {
        if (!$advancedLeaveRequest->approval_status->is(ApprovalStatus::PENDING)) {
            return $this->errorResponse(message: 'Status can not be changed', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $availableDays = AdvancedLeaveRequestService::getAvailableDays($advancedLeaveRequest->user);
        if ($advancedLeaveRequest->amount > $availableDays) {
            $message = $availableDays == 0 ? 'User have no available days' : 'Request days exceeds available days';
            return $this->errorResponse(message: $message, code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $advancedLeaveRequest->update($request->validated());
            if ($advancedLeaveRequest->approval_status->is(ApprovalStatus::APPROVED)) {
                AdvancedLeaveRequestService::updateMonths($advancedLeaveRequest);
                // UserTimeoffHistory::create([
                //     'is_for_total_timeoff' => true,
                //     'user_id' => $advancedLeaveRequest->user->id,
                //     'is_increment' => true,
                //     'value' => $advancedLeaveRequest->amount,
                //     'properties' => ['user' => $advancedLeaveRequest->user],
                //     'description' => UserTimeoffHistory::DESCRIPTION['ADVANCED_LEAVE'],
                // ]);
            }

            $notificationType = NotificationType::ADVANCED_LEAVE_APPROVED;
            $advancedLeaveRequest->user->notify(new ($notificationType->getNotificationClass())($notificationType, $advancedLeaveRequest->approvedBy, $advancedLeaveRequest->approval_status, $advancedLeaveRequest));
            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new AdvancedLeaveRequestResource($advancedLeaveRequest);
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = DB::table('advanced_leave_requests')->where('approved_by', auth('sanctum')->id())->where('approval_status', $request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = AdvancedLeaveRequest::where('approved_by', auth('sanctum')->id())->with('user', fn($q) => $q->select('id', 'name'));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                'approval_status'
            ])
            ->allowedSorts([
                'id',
                'date',
            ])
            ->paginate($this->per_page);

        return AdvancedLeaveRequestResource::collection($data);
    }

    public function getAvailableDays()
    {
        $availableDays = AdvancedLeaveRequestService::getAvailableDays();

        return response()->json(['data' => ['available_days' => $availableDays]]);
    }
}
