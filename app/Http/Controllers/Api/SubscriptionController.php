<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Subscription\StoreRequest;
use App\Http\Requests\Api\Subscription\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\SubscriptionServiceInterface;
use App\Models\Subscription;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SubscriptionController extends BaseController
{
    public function __construct(protected SubscriptionServiceInterface $service)
    {
        parent::__construct();
        // $this->middleware('permission:subscription_access', ['only' => ['restore']]);
        // $this->middleware('permission:subscription_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:subscription_create', ['only' => 'store']);
        // $this->middleware('permission:subscription_edit', ['only' => 'update']);
        // $this->middleware('permission:subscription_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Subscription::class)
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'name',
                'account_no',
                'account_holder',
                'code',
                'branch',
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'account_no',
                'account_holder',
                'code',
                'branch',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $subscription = $this->service->findById($id);
        return new DefaultResource($subscription);
    }

    public function store(StoreRequest $request)
    {
        $subscription = $this->service->create($request->validated());

        return new DefaultResource($subscription);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $this->service->findById($id);
        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $this->service->findById($id);
        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $this->service->forceDelete($id);

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $this->service->restore($id);

        return $this->okResponse();
    }
}
