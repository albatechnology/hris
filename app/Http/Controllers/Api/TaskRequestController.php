<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Enums\NotificationType;
use App\Http\Requests\Api\TaskRequest\StoreRequest;
use App\Http\Requests\Api\TaskRequest\ApproveRequest;
use App\Http\Resources\DefaultResource;
use App\Models\TaskRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

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
        $data = QueryBuilder::for(TaskRequest::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('task_hour_id'),
                AllowedFilter::callback('task_id', fn ($query, $value) => $query->whereHas('taskHour', fn ($q) => $q->where('task_id', $value))),
                'approval_status'
            ])
            ->allowedIncludes([
                AllowedInclude::callback('taskHour', fn ($q) => $q->select('id', 'name', 'task_id')->with('task', fn ($q) => $q->select('id', 'name'))),
            ])
            ->allowedSorts([
                'id', 'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(TaskRequest $taskRequest): DefaultResource
    {
        return new DefaultResource($taskRequest->load(['taskHour.task', 'media']));
    }

    public function store(StoreRequest $request): DefaultResource|JsonResponse
    {
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
        if (!$taskRequest->approval_status->is(\App\Enums\ApprovalStatus::PENDING)) {
            return $this->errorResponse(message: 'Task request can not be deleted', code: 400);
        }

        $taskRequest->delete();
        return $this->deletedResponse();
    }

    public function approve(ApproveRequest $request, TaskRequest $taskRequest): DefaultResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $taskRequest->update($request->validated());

            $notificationType = NotificationType::TASK_APPROVED;
            $taskRequest->user->notify(new ($notificationType->getNotificationClass())($notificationType, $taskRequest->approvedBy, $taskRequest->approval_status, $taskRequest));
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new DefaultResource($taskRequest);
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = DB::table('task_requests')->where('approved_by', auth('sanctum')->id())->where('approval_status', $request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = TaskRequest::whereHas('user', fn ($q) => $q->where('approval_id', auth('sanctum')->id()))
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

        return DefaultResource::collection($data);
    }
}
