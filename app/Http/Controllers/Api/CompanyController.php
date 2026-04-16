<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Company\StoreRequest;
use App\Http\Requests\Api\Company\UpdateRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Interfaces\Services\Company\CompanyServiceInterface;
use App\Models\Company;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class CompanyController extends BaseController
{
    public function __construct(private CompanyServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return [];
    }

    public function index()
    {
        Gate::authorize('viewAny', Company::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->tenanted(),
            [
                AllowedFilter::exact('group_id'),
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
                'group_id',
                'name',
                'country',
                'province',
                'city',
                'zip_code',
                'address',
                'created_at',
            ],
            [
                'id',
                'group_id',
                'name',
                'country',
                'province',
                'city',
                'zip_code',
                'address',
                'created_at',
            ],
        );

        return CompanyResource::collection($datas);
    }

    public function show(string $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new CompanyResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Company::class);

        $data = $this->service->create($request->validated());
        return new CompanyResource($data);
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
}
