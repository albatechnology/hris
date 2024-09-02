<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserPatrolTask\StoreRequest;
use App\Http\Requests\Api\UserPatrolTask\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\UserPatrolTask;
use Exception;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserPatrolTaskController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:patrol_access', ['only' => ['restore']]);
        $this->middleware('permission:patrol_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:patrol_create', ['only' => 'store']);
        $this->middleware('permission:patrol_edit', ['only' => 'update']);
        $this->middleware('permission:patrol_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(UserPatrolTask::tenanted())
            ->allowedIncludes(['patrolTask', 'user'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('patrol_task_id'),
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'patrol_task_id',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $userPatrolTask = UserPatrolTask::findTenanted($id);
        $userPatrolTask->load(['patrolTask', 'user']);

        return new DefaultResource($userPatrolTask);
    }

    public function store(StoreRequest $request)
    {
        try {
            $userPatrolTask = UserPatrolTask::create($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($userPatrolTask);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $userPatrolTask = UserPatrolTask::findTenanted($id);

        try {
            $userPatrolTask->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($userPatrolTask))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $userPatrolTask = UserPatrolTask::findTenanted($id);
        $userPatrolTask->delete();

        return $this->deletedResponse();
    }
}
