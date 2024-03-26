<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Http\Requests\Api\OvertimeRequest\StoreRequest;
use App\Http\Requests\Api\OvertimeRequest\ApproveRequest;
use App\Http\Resources\OvertimeRequest\OvertimeRequestResource;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Exception;
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
        $data = QueryBuilder::for(OvertimeRequest::query())
            ->allowedFilters([
                AllowedFilter::exact('id'),
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
        // $time = strtotime(date('H:i'));
        // $duration = $time / 1000;
        // $hours = floor($duration / 3600);
        // $minutes = floor(($duration / 60) % 60);
        // $seconds = $duration % 60;
        // if ($hours != 0)
        //     echo "$hours:$minutes:$seconds";
        // else
        //     echo "$minutes:$seconds";
        // die;
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

            $notificationType = NotificationType::REQUEST_OVERTIME;
            $overtimeRequest->user->manager?->notify(new ($notificationType->getNotificationClass())($notificationType, $overtimeRequest->user, $overtimeRequest));
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeRequestResource($overtimeRequest);
    }

    public function approve(ApproveRequest $request, OvertimeRequest $overtimeRequest): OvertimeRequestResource|JsonResponse
    {
        try {
            $overtimeRequest->update($request->validated());

            $notificationType = NotificationType::OVERTIME_APPROVED;
            $overtimeRequest->user->notify(new ($notificationType->getNotificationClass())($notificationType, $overtimeRequest->approvedBy, $overtimeRequest->is_approved, $overtimeRequest));
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeRequestResource($overtimeRequest);
    }

    public function approvals()
    {
        $query = OvertimeRequest::whereHas('user', fn ($q) => $q->where('manager_id', auth('sanctum')->id()));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
            ])
            ->allowedSorts([
                'id', 'date',
            ])
            ->paginate($this->per_page);

        return OvertimeRequestResource::collection($data);
    }
}
