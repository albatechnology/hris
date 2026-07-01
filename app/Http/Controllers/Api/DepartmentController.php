<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Department\StoreRequest;
use App\Http\Resources\DefaultResource;
use Spatie\QueryBuilder\AllowedInclude;
use App\Interfaces\Services\Department\DepartmentServiceInterface;
use App\Models\Department;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class DepartmentController extends BaseController
{
    public function __construct(private DepartmentServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return ['division'];
    }

    private function getAllowedIncludes()
    {
        return [
            AllowedInclude::callback('company', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('division', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('user', function ($query) {
                $query->select('id', 'name');
            }),
        ];
    }

    public function index()
    {
        Gate::authorize('viewAny', Department::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->tenanted(),
            [
                AllowedFilter::exact('division_id'),
                AllowedFilter::scope('company_id'),
                AllowedFilter::exact('division_id'),
                AllowedFilter::exact('user_id'),
                'name',
            ],
            $this->allowedIncludes(),
            [
                'id',
                'company_id',
                'division_id',
                'user_id',
                'name',
                'created_at',
            ],
        );

        return DefaultResource::collection($datas);
    }

    public function show(string $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Department::class);

        $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(string $id, StoreRequest $request)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('forceDelete', $data);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('restore', $data);

        $this->service->restore($id);

        return $this->restoredResponse();
    }
}
