<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\MediaCollection;
use App\Enums\NotificationType;
use App\Http\Requests\Api\TaskRequest\StoreRequest;
use App\Http\Requests\Api\TaskRequest\ApproveRequest;
use App\Http\Resources\DefaultResource;
use App\Models\TaskRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class TaskRequestController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:task_request_access', ['only' => ['restore']]);
        $this->middleware('permission:task_request_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:task_request_create', ['only' => 'store']);
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(TaskRequest::tenanted()->with('approvals', fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))))
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('task_hour_id'),
                AllowedFilter::callback('task_id', fn($query, $value) => $query->whereHas('taskHour', fn($q) => $q->where('task_id', $value))),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
            ])
            ->allowedIncludes([
                AllowedInclude::callback('taskHour', fn($q) => $q->select('id', 'name', 'task_id')->with('task', fn($q) => $q->select('id', 'name'))),
            ])
            ->allowedSorts([
                'id',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id): DefaultResource
    {
        $taskRequest = TaskRequest::findTenanted($id);
        return new DefaultResource($taskRequest->load(['taskHour.task', 'media']));
    }

    public function store(StoreRequest $request): DefaultResource|JsonResponse
    {
        $user = User::select('id', 'company_id')->where('id', $request->user_id)->firstOrFail();
        if (AttendanceService::inLockAttendance($request->start_at, $user) || AttendanceService::inLockAttendance($request->end_at, $user)) {
            throw new UnprocessableEntityHttpException('Attendance is locked');
        }

        DB::beginTransaction();
        try {
            $taskRequest = TaskRequest::create($request->validated());
            if ($request->hasFile('files')) {
                $mediaCollection = MediaCollection::TASK->value;
                foreach ($request->file('files') as $file) {
                    if ($file->isValid()) $taskRequest->addMedia($file)->toMediaCollection($mediaCollection);
                }
            }

            $notificationType = NotificationType::REQUEST_TASK;
            $taskRequest->user->approval?->notify(new ($notificationType->getNotificationClass())($notificationType, $taskRequest->user, $taskRequest));
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new DefaultResource($taskRequest);
    }

    public function update(int $id, StoreRequest $request)
    {
        $taskRequest = TaskRequest::findTenanted($id);
        if (!$taskRequest->approval_status->is(\App\Enums\ApprovalStatus::PENDING)) {
            return $this->errorResponse(message: 'Task request can not be updated', code: 400);
        }

        DB::beginTransaction();
        try {
            $taskRequest->update($request->validated());
            if ($request->hasFile('files')) {
                $mediaCollection = MediaCollection::TASK->value;
                $taskRequest->clearMediaCollection($mediaCollection);
                foreach ($request->file('files') as $file) {
                    if ($file->isValid()) $taskRequest->addMedia($file)->toMediaCollection($mediaCollection);
                }
            }

            $notificationType = NotificationType::REQUEST_TASK;
            $taskRequest->user->approval?->notify(new ($notificationType->getNotificationClass())($notificationType, $taskRequest->user, $taskRequest));
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new DefaultResource($taskRequest);
    }

    public function destroy(int $id)
    {
        $taskRequest = TaskRequest::findTenanted($id);
        // if (!$taskRequest->approval_status->is(\App\Enums\ApprovalStatus::PENDING)) {
        //     return $this->errorResponse(message: 'Task request can not be deleted', code: 400);
        // }

        $taskRequest->delete();
        return $this->deletedResponse();
    }

    public function approve(ApproveRequest $request, int $id): DefaultResource|JsonResponse
    {
        $taskRequest = TaskRequest::findTenanted($id);
        $requestApproval = $taskRequest->approvals()->where('user_id', auth()->id())->first();

        if (!$requestApproval) return $this->errorResponse(message: 'You are not registered as approved', code: Response::HTTP_NOT_FOUND);

        if (!$taskRequest->isDescendantApproved()) return $this->errorResponse(message: 'You have to wait for your subordinates to approve', code: Response::HTTP_UNPROCESSABLE_ENTITY);

        if ($taskRequest->approval_status == ApprovalStatus::APPROVED->value || $requestApproval->approval_status->in([ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])) {
            return $this->errorResponse(message: 'Status can not be changed', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $requestApproval->update($request->validated());
            // $taskRequest->update($request->validated());

            // $notificationType = NotificationType::TASK_APPROVED;
            // $taskRequest->user->notify(new ($notificationType->getNotificationClass())($notificationType, $taskRequest->approvedBy, $taskRequest->approval_status, $taskRequest));
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return $this->updatedResponse();
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = TaskRequest::myApprovals()
            ->whereApprovalStatus($request->filter['approval_status'])
            ->when($request->branch_id, fn($q) => $q->whereBranch($request->branch_id))
            ->when($request->name, fn($q) => $q->whereUserName($request->name))
            ->when($request->created_at, fn($q) => $q->createdAt($request->created_at))
            ->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = TaskRequest::myApprovals()
            ->with([
                'user' => fn($q) => $q->select('id', 'name'),
                // 'approvedBy' => fn($q) => $q->select('id', 'name'),
                'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))
            ]);

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                AllowedFilter::scope('branch_id', 'whereBranch'),
                AllowedFilter::scope('name', 'whereUserName'),
                'created_at',
            ])
            ->allowedIncludes([
                AllowedInclude::callback('taskHour', fn($q) => $q->select('id', 'name', 'task_id')->with('task', fn($q) => $q->select('id', 'name'))),
            ])
            ->allowedSorts([
                'id',
                'date',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }
}
