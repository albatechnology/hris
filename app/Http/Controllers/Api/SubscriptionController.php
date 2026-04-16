<?php

namespace App\Http\Controllers\Api;

use App\Models\Subscription;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\DefaultResource;
use App\Http\Requests\Api\Subscription\StoreRequest;
use App\Http\Requests\Api\Subscription\UpdateRequest;
use App\Interfaces\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends BaseController
{
    public function __construct(private SubscriptionServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        Gate::authorize('viewAny', Subscription::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            null,
            [
                AllowedFilter::exact('company_id'),
                'name',
                'account_no',
                'account_holder',
                'code',
                'branch',
            ],
            [],
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

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $subscription = $this->service->findById($id);
        Gate::authorize('view', $subscription);

        return new DefaultResource($subscription);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Subscription::class);

        $subscription = $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(int $id, UpdateRequest $request)
    {
        $subscription = $this->service->findById($id);
        Gate::authorize('update', $subscription);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $subscription = $this->service->findById($id);
        Gate::authorize('delete', $subscription);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $subscription = $this->service->findById($id, fn($q) => $q->withTrashed());
        Gate::authorize('forceDelete', $subscription);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(int $id)
    {
        $subscription = $this->service->findById($id, fn($q) => $q->withTrashed());
        Gate::authorize('restore', $subscription);

        $this->service->restore($id);

        return $this->restoredResponse();
    }

    public function quotaInfo()
    {
        $data = $this->service->getQuotaInfo();
        return new DefaultResource($data);
    }
}
