<?php

namespace App\Http\Controllers\Api;

use App\Enums\TimeoffPolicyType;
use App\Http\Requests\Api\TimeoffQuota\StoreRequest;
use App\Http\Requests\Api\TimeoffQuota\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Http\Resources\TimeoffQuota\TimeoffQuotaMe;
use App\Models\TimeoffQuota;
use Exception;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffQuotaController extends BaseController
{
    // public function __construct()
    // {
    //     parent::__construct();
    //     $this->middleware('permission:timeoff_policy_access', ['only' => ['restore']]);
    //     $this->middleware('permission:timeoff_policy_read', ['only' => ['index', 'show']]);
    //     $this->middleware('permission:timeoff_policy_create', ['only' => 'store']);
    //     $this->middleware('permission:timeoff_policy_edit', ['only' => 'update']);
    //     $this->middleware('permission:timeoff_policy_delete', ['only' => ['destroy', 'forceDelete']]);
    // }

    public function me()
    {
        $data = TimeoffQuota::select('timeoff_policy_id', DB::raw('SUM(quota - used_quota) as remaining_balance'))
            ->with('timeoffPolicy', fn($q) => $q->select('id', 'name', 'code'))
            ->where('user_id', auth()->id())
            ->whereHas('timeoffPolicy', fn($q) => $q->whereIn('type', TimeoffPolicyType::hasQuotas()))
            ->groupBy('timeoff_policy_id')
            ->paginate();

        return TimeoffQuotaMe::collection($data);
    }

    public function index()
    {
        $data = QueryBuilder::for(TimeoffQuota::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('timeoff_policy_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::scope('effective_start_date'),
                AllowedFilter::scope('effective_end_date'),
            ])
            ->allowedIncludes(['timeoffPolicy', 'user'])
            ->allowedSorts([
                'id',
                'timeoff_policy_id',
                'user_id',
                'effective_start_date',
                'effective_end_date',
                'quota',
                'used_quota',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $data = QueryBuilder::for(TimeoffQuota::tenanted()->where('id', $id))
            ->allowedIncludes(['timeoffPolicy', 'user'])
            ->firstOrFail();

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $timeoffQuota = TimeoffQuota::create($request->validated());

            $timeoffQuota->timeoffQuotaHistories()->create([
                'user_id' => $timeoffQuota->user_id,
                'is_increment' => true,
                'new_balance' => $timeoffQuota->quota,
                'description' => $request->description,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($timeoffQuota);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $timeoffQuota = TimeoffQuota::findTenanted($id);
        $oldBalance = $timeoffQuota->balance;
        $requestedBalance = (float)$request->quota;
        $data = $request->validated();

        if ($isIncrement = boolval($requestedBalance >= $oldBalance)) {
            $newBalance = $oldBalance + ($requestedBalance - $oldBalance);
            $data['quota'] = $timeoffQuota->quota + ($requestedBalance - $oldBalance);
        } else {
            $newBalance = $requestedBalance;
            $data['quota'] = $timeoffQuota->quota - ($oldBalance - $requestedBalance);
        }

        DB::beginTransaction();
        try {
            $timeoffQuota->update($data);

            $timeoffQuota->timeoffQuotaHistories()->create([
                'user_id' => $timeoffQuota->user_id,
                'is_increment' => $isIncrement,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'description' => $request->description,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($timeoffQuota);
    }

    public function destroy(TimeoffQuota $timeoffQuota)
    {
        $timeoffQuota->delete();

        return $this->deletedResponse();
    }

    // public function forceDelete($id)
    // {
    //     $timeoffQuota = TimeoffQuota::withTrashed()->findOrFail($id);
    //     $timeoffQuota->forceDelete();

    //     return $this->deletedResponse();
    // }

    // public function restore($id)
    // {
    //     $timeoffQuota = TimeoffQuota::withTrashed()->findOrFail($id);
    //     $timeoffQuota->restore();

    //     return new DefaultResource($timeoffQuota);
    // }
}
