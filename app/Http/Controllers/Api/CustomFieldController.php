<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CustomField\StoreRequest;
use App\Http\Resources\CustomField\CustomFieldResource;
use App\Interfaces\Services\CustomField\CustomFieldServiceInterface;
use App\Models\CustomField;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class CustomFieldController extends BaseController
{
    public function __construct(private CustomFieldServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return ['company'];
    }

    public function index()
    {
        Gate::authorize('viewAny', CustomField::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->tenanted(),
            [
                AllowedFilter::exact('company_id'),
            ],
            $this->allowedIncludes(),
            [
                'id',
                'company_id',
                'key',
                'type',
                'options',
                'created_at',
            ],
        );

        return CustomFieldResource::collection($datas);
    }

    public function show(string $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new CustomFieldResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', CustomField::class);

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
