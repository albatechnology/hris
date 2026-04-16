<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Task\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\Task\TaskServiceInterface;
use App\Models\Task;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class TaskController extends BaseController
{
    public function __construct(private TaskServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        Gate::authorize('viewAny', Task::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            null,
            [
                AllowedFilter::exact('company_id'),
                'name',
                'working_period',
            ],
            ['company', 'hours'],
            [
                'id',
                'company_id',
                'name',
                'working_period'
            ],
        );

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $task = $this->service->findById($id);
        Gate::authorize('view', $task);

        $task->load(['company', 'hours']);
        return new DefaultResource($task);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Task::class);

        $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(int $id, StoreRequest $request)
    {
        $task = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $task);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $task = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $task);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $task = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('forceDelete', $task);

        $this->service->forceDelete($id);

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $task = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('restore', $task);

        $this->service->restore($id);

        return $this->restoredResponse();
    }
}
