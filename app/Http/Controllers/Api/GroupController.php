<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Group\StoreRequest;
use App\Http\Resources\Group\GroupResource;
use App\Interfaces\Services\Group\GroupServiceInterface;
use App\Models\Group;
use Illuminate\Support\Facades\Gate;

class GroupController extends BaseController
{
    public function __construct(private GroupServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        Gate::authorize('viewAny', Group::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            null,
            [
                'name',
            ],
            [],
            [
                'id',
                'name',
                'created_at',
            ],
        );

        return GroupResource::collection($datas);
    }

    public function show(string $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new GroupResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Group::class);

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
