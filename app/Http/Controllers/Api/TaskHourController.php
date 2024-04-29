<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\TaskHour\StoreRequest;
use App\Http\Requests\Api\TaskHour\StoreUsersRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Task;
use App\Models\TaskHour;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TaskHourController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:task_access', ['only' => ['restore']]);
        $this->middleware('permission:task_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:task_create', ['only' => 'store']);
        $this->middleware('permission:task_edit', ['only' => 'update']);
        $this->middleware('permission:task_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    private function getTaskHour(int $id){
        return TaskHour::where('id', $id)->whereHas('task', fn ($q) => $q->tenanted())->firstOrFail();
    }

    public function index()
    {
        $data = QueryBuilder::for(TaskHour::whereHas('task', fn ($q) => $q->tenanted())->withCount('users'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('task_id'),
                'name',
            ])
            ->allowedIncludes(['task'])
            ->allowedSorts([
                'id', 'name'
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $taskHour = $this->getTaskHour($id);
        return new DefaultResource($taskHour->loadCount('users'));
    }

    public function store(StoreRequest $request)
    {
        $taskHour = TaskHour::create($request->validated());

        return new DefaultResource($taskHour);
    }

    public function update(int $id, StoreRequest $request)
    {
        $taskHour = $this->getTaskHour($id);

        try {
            $taskHour->update($request->validated());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $taskHour = $this->getTaskHour($id);

        $taskHour->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $taskHour = $this->getTaskHour($id);
        $taskHour->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $taskHour = $this->getTaskHour($id);
        $taskHour->restore();

        return new DefaultResource($taskHour);
    }

    public function users(int $id)
    {
        $query = \App\Models\User::select('id', 'name')->whereHas('tasks', fn ($q) => $q->where('task_hour_id', $id));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                'name'
            ])
            ->allowedSorts([
                'id', 'name'
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function addUsers(int $id, StoreUsersRequest $request)
    {
        $taskHour = $this->getTaskHour($id);

        try {
            $taskHour->task->users()->attach($request->user_ids, ['task_hour_id' => $id]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->createdResponse();
    }

    public function deleteUsers(int $id, StoreUsersRequest $request)
    {
        $taskHour = $this->getTaskHour($id);

        try {
            $taskHour->task->users()->toggle($request->user_ids);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }
}
