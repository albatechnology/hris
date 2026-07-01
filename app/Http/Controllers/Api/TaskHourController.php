<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\TaskHour\StoreRequest;
use App\Http\Requests\Api\TaskHour\StoreUsersRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\TaskHour\TaskHourServiceInterface;
use App\Models\TaskHour;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TaskHourController extends BaseController
{
    public function __construct(private TaskHourServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        Gate::authorize('viewAny', TaskHour::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            null,
            [
                AllowedFilter::exact('task_id'),
                'name',
            ],
            ['task'],
            [
                'id',
                'name',
            ],
        );

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $data = $this->service->findByIdOrFail($id);
        Gate::authorize('view', $data);

        return new DefaultResource($data->loadCount('users'));
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', TaskHour::class);

        $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(int $id, StoreRequest $request)
    {
        $data = $this->service->findByIdOrFail($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $data = $this->service->findByIdOrFail($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function users(int $id)
    {
        $taskHour = $this->service->findByIdOrFail($id, fn($q) => $q->select('id'));
        Gate::authorize('view', $taskHour);

        $query = \App\Models\User::select('id', 'name', 'nik', 'branch_id', 'company_id')
            ->tenanted()
            ->whereHas('tasks', fn($q) => $q->where('task_hour_id', $id))
            ->with([
                'company' => fn($q) => $q->select('id', 'name'),
                'branch' => fn($q) => $q->select('id', 'name'),
            ]);

        $datas = QueryBuilder::for($query)
            ->allowedFilters([
                'name'
            ])
            ->allowedSorts([
                'id',
                'name'
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($datas);
    }

    public function addUsers(int $id, StoreUsersRequest $request)
    {
        $data = $this->service->findByIdOrFail($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $this->service->addUsers($id, $request->user_ids);

        return $this->createdResponse();
    }

    public function deleteUsers(int $id, StoreUsersRequest $request)
    {
        $data = $this->service->findByIdOrFail($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $this->service->deleteUsers($id, $request->user_ids);

        return $this->deletedResponse();
    }
}
