<?php

namespace App\Http\Controllers\Api;

use App\Enums\TimeoffPolicyType;
use App\Http\Requests\Api\TimeoffPolicy\StoreRequest;
use App\Http\Resources\TimeoffPolicy\TimeoffPolicyResource;
use App\Interfaces\Services\TimeoffPolicy\TimeoffPolicyServiceInterface;
use App\Models\TimeoffPolicy;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class TimeoffPolicyController extends BaseController
{
    public function __construct(private TimeoffPolicyServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        Gate::authorize('viewAny', TimeoffPolicy::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            null,
            [
                AllowedFilter::exact('company_id'),
                AllowedFilter::scope('start_effective_date'),
                AllowedFilter::scope('end_effective_date'),
                AllowedFilter::callback('has_quota', function ($query, bool $value) {
                    if ($value === true) {
                        $query->whereIn('type', TimeoffPolicyType::hasQuotas());
                    }
                }),
                'type',
                'name',
                'code',
                'is_allow_halfday',
                // 'is_for_all_user',
                // 'is_enable_block_leave',
                // 'is_unlimited_day',
            ],
            ['company'],
            [
                'id',
                'company_id',
                'effective_date',
                'expired_date',
                'type',
                'name',
                'code',
                'is_allow_halfday',
                // 'is_for_all_user',
                // 'is_enable_block_leave',
                // 'is_unlimited_day',
                'created_at',
            ],
        );

        return TimeoffPolicyResource::collection($datas);
    }

    public function show(int $id)
    {
        $timeoffPolicy = $this->service->findByIdOrFail($id, null, ['company']);
        Gate::authorize('view', $timeoffPolicy);

        return new TimeoffPolicyResource($timeoffPolicy);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', TimeoffPolicy::class);

        $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(int $id, StoreRequest $request)
    {
        $timeoffPolicy = $this->service->findByIdOrFail($id);
        Gate::authorize('update', $timeoffPolicy);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $timeoffPolicy = $this->service->findByIdOrFail($id);
        Gate::authorize('delete', $timeoffPolicy);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $timeoffPolicy = $this->service->findByIdOrFail($id, fn($q) => $q->withTrashed());
        Gate::authorize('forceDelete', $timeoffPolicy);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(int $id)
    {
        $timeoffPolicy = $this->service->findByIdOrFail($id, fn($q) => $q->withTrashed());
        Gate::authorize('restore', $timeoffPolicy);

        $this->service->restore($id);

        return $this->restoredResponse();
    }
}
