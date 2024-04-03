<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Http\Requests\Api\AdvancedLeaveRequest\StoreRequest;
use App\Http\Requests\Api\AdvancedLeaveRequest\ApproveRequest;
use App\Http\Resources\AdvancedLeaveRequest\AdvancedLeaveRequestResource;
use App\Models\AdvancedLeaveRequest;
use App\Models\User;
use App\Models\UserTimeoffHistory;
use App\Services\AdvancedLeaveRequestService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

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
        $data = QueryBuilder::for(AdvancedLeaveRequest::query())
            ->allowedFilters([
                AllowedFilter::exact('id'),
            ])
            ->allowedSorts([
                'id', 'date',
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
        $availableDays = AdvancedLeaveRequestService::getAvailableDays(User::findOrFail($request->user_id));
        if ($request->amount > $availableDays) {
            $message = $availableDays == 0 ? 'You have no available days' : 'Request days exceeds available days';
            return $this->errorResponse(message: $message, code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $advancedLeaveRequest = AdvancedLeaveRequest::create($request->validated());

            $notificationType = NotificationType::REQUEST_ADVANCED_LEAVE;
            $advancedLeaveRequest->user->manager?->notify(new ($notificationType->getNotificationClass())($notificationType, $advancedLeaveRequest->user, $advancedLeaveRequest));
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new AdvancedLeaveRequestResource($advancedLeaveRequest);
    }

    public function approve(ApproveRequest $request, AdvancedLeaveRequest $advancedLeaveRequest): AdvancedLeaveRequestResource|JsonResponse
    {
        if (!is_null($advancedLeaveRequest->is_approved)) {
            return $this->errorResponse(message: 'Status can not be changed', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $advancedLeaveRequest->update($request->validated());
            if ($advancedLeaveRequest->is_approved == 1) {
                AdvancedLeaveRequestService::updateMonths($advancedLeaveRequest);
                UserTimeoffHistory::create([
                    'is_for_total_timeoff' => true,
                    'user_id' => $advancedLeaveRequest->user->id,
                    'is_increment' => true,
                    'value' => $advancedLeaveRequest->amount,
                    'properties' => ['user' => $advancedLeaveRequest->user],
                    'description' => UserTimeoffHistory::DESCRIPTION['ADVANCED_LEAVE'],
                ]);
            }

            $notificationType = NotificationType::ADVANCED_LEAVE_APPROVED;
            $advancedLeaveRequest->user->notify(new ($notificationType->getNotificationClass())($notificationType, $advancedLeaveRequest->approvedBy, $advancedLeaveRequest->is_approved, $advancedLeaveRequest));
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new AdvancedLeaveRequestResource($advancedLeaveRequest);
    }

    public function approvals()
    {
        $query = AdvancedLeaveRequest::whereHas('user', fn ($q) => $q->where('parent_id', auth('sanctum')->id()));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
            ])
            ->allowedSorts([
                'id', 'date',
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
