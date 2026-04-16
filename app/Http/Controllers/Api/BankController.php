<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Bank\StoreRequest;
use App\Http\Requests\Api\Bank\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\Bank\BankServiceInterface;
use App\Models\Bank;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class BankController extends BaseController
{
    public function __construct(protected BankServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return [];
    }

    public function index()
    {
        Gate::authorize('viewAny', Bank::class);

        $data = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->tenanted(),
            [
                AllowedFilter::exact('company_id'),
                'name',
                'account_no',
                'account_holder',
                'code',
                'branch',
            ],
            $this->allowedIncludes(),
            [
                'id',
                'company_id',
                'name',
                'account_no',
                'account_holder',
                'code',
                'branch',
                'created_at',
            ],
            [
                'id',
                'company_id',
                'name',
                'account_no',
                'account_holder',
                'code',
                'branch',
                'created_at',
            ],
        );

        return DefaultResource::collection($data);
    }

    public function show(string $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Bank::class);

        $data = $this->service->create($request->validated());

        return new DefaultResource($data);
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
