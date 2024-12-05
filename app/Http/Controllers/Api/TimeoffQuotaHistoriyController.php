<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\TimeoffQuotaHistory\StoreRequest;
use App\Http\Requests\Api\TimeoffQuotaHistory\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\TimeoffQuotaHistory;
use Exception;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffQuotaHistoryController extends BaseController
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

    public function index()
    {
        $data = QueryBuilder::for(TimeoffQuotaHistory::tenanted())
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
        $data = QueryBuilder::for(TimeoffQuotaHistory::tenanted()->where('id', $id))
            ->allowedIncludes(['timeoffPolicy', 'user'])
            ->firstOrFail();

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $timeoffQuotaHistory = TimeoffQuotaHistory::create($request->validated());

            $timeoffQuotaHistory->timeoffQuotaHistoryHistories()->create([
                'user_id' => $timeoffQuotaHistory->user_id,
                'is_increment' => true,
                'new_balance' => $timeoffQuotaHistory->quota,
                'description' => $request->description,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($timeoffQuotaHistory);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $timeoffQuotaHistory = TimeoffQuotaHistory::findTenanted($id);
        $oldBalance = $timeoffQuotaHistory->balance;
        $newBalance = (float)$request->quota;

        DB::beginTransaction();
        try {
            $timeoffQuotaHistory->update($request->validated());

            $timeoffQuotaHistory->timeoffQuotaHistoryHistories()->create([
                'user_id' => $timeoffQuotaHistory->user_id,
                'is_increment' => boolval($newBalance >= $oldBalance),
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'description' => $request->description,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($timeoffQuotaHistory);
    }

    public function destroy(int $id)
    {
        $timeoffQuotaHistory = TimeoffQuotaHistory::findTenanted($id);
        $timeoffQuotaHistory->delete();

        return $this->deletedResponse();
    }

    // public function forceDelete($id)
    // {
    //     $timeoffQuotaHistory = TimeoffQuotaHistory::withTrashed()->findOrFail($id);
    //     $timeoffQuotaHistory->forceDelete();

    //     return $this->deletedResponse();
    // }

    // public function restore($id)
    // {
    //     $timeoffQuotaHistory = TimeoffQuotaHistory::withTrashed()->findOrFail($id);
    //     $timeoffQuotaHistory->restore();

    //     return new DefaultResource($timeoffQuotaHistory);
    // }
}
