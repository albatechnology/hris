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

    public function index(Task $task)
    {
        $data = QueryBuilder::for(TaskHour::where('task_id', $task->id)->withCount('users'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'name',
            ])
            ->allowedIncludes(['task'])
            ->allowedSorts([
                'id', 'name'
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(Task $task, TaskHour $hour)
    {
        return new DefaultResource($hour->loadCount('users'));
    }

    public function store(Task $task, StoreRequest $request)
    {
        $taskHour = $task->hours()->create($request->validated());

        return new DefaultResource($taskHour);
    }

    public function update(Task $task, $id, StoreRequest $request)
    {
        try {
            $task->hours()->where('id', $id)->update($request->validated());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->updatedResponse();
    }

    public function destroy(Task $task, $id)
    {
        $task->hours()->where('id', $id)->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(Task $task, $id)
    {
        $taskHour = $task->hours()->where('id', $id)->withTrashed()->findOrFail($id);
        $taskHour->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(Task $task, $id)
    {
        $taskHour = $task->hours()->where('id', $id)->withTrashed()->findOrFail($id);
        $taskHour->restore();

        return new DefaultResource($taskHour);
    }

    public function users($taskId, $id)
    {
        $query = \App\Models\User::select('id', 'name')->whereHas('tasks', fn ($q) => $q->where('task_id', $taskId)->where('task_hour_id', $id));

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

    public function addUsers(Task $task, $id, StoreUsersRequest $request)
    {
        try {
            $task->users()->attach($request->user_ids, ['task_hour_id' => $id]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->createdResponse();
    }

    public function deleteUsers(Task $task, $id, StoreUsersRequest $request)
    {
        try {
            $task->users()->toggle($request->user_ids);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }
}
