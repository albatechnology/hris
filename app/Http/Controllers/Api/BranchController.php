<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Branch\StoreRequest;
use App\Http\Requests\Api\Branch\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\Branch\BranchServiceInterface;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class BranchController extends BaseController
{
    public function __construct(private BranchServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return ['parent', 'childs'];
    }

    public function index()
    {
        Gate::authorize('viewAny', Branch::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->tenanted(),
            [
                AllowedFilter::exact('parent_id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::callback('company_ids', fn($q, $value) => $q->whereIn('company_id', $value)),
                AllowedFilter::scope('is_parent', 'whereIsParent'),
                'name',
                'country',
                'province',
                'city',
                'zip_code',
                'address',
            ],
            $this->allowedIncludes(),
            [
                'id',
                'company_id',
                'name',
                'country',
                'province',
                'city',
                'zip_code',
                'address',
                'created_at',
            ],
            ['id', 'name'],
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
        Gate::authorize('create', Branch::class);

        $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(string $id, UpdateRequest $request)
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

    public function summary(Request $request)
    {
        $branchId = $request->query('branch_id');

        return new DefaultResource($this->service->summary($branchId ? (int) $branchId : null));
    }
}
