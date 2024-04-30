<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Task\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Task;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TaskController extends BaseController
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

    public function index()
    {
        $data = QueryBuilder::for(Task::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                'name', 'working_period',
            ])
            ->allowedIncludes(['company','hours'])
            ->allowedSorts([
                'id', 'company_id', 'name', 'working_period'
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $task = QueryBuilder::for(Task::tenanted()->where('id', $id))
            ->allowedIncludes(['company','hours'])
            ->firstOrFail();

        return new DefaultResource($task);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $task = Task::create($request->validated());
            if ($request->hours) {
                $task->hours()->createMany($request->hours);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($task);
    }

    public function update(Task $task, StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $task->update($request->validated());
            if ($request->hours) {
                $hours = collect($request->hours);
                $task->hours()->whereNotIn('id', $hours->pluck('id'))->delete();
                $hours->unique('id')->each(fn ($hour) => $task->hours()->updateOrCreate(['id' => $hour['id']], $hour));
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($task))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $task = Task::withTrashed()->findOrFail($id);
        $task->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $task = Task::withTrashed()->findOrFail($id);
        $task->restore();

        return new DefaultResource($task);
    }
}
