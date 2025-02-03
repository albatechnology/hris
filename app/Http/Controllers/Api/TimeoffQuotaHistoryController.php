<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DefaultResource;
use App\Models\TimeoffQuotaHistory;
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
        $data = QueryBuilder::for(
            TimeoffQuotaHistory::tenanted()
                ->with([
                    'timeoffQuota' => fn($q) => $q->withTrashed()->select('id', 'timeoff_policy_id')->with('timeoffPolicy', fn($q) => $q->withTrashed()->select('id', 'name')),
                    'user' => fn($q) => $q->select('id', 'name', 'last_name'),
                    'createdBy' => fn($q) => $q->select('id', 'name', 'last_name'),
                    'updatedBy' => fn($q) => $q->select('id', 'name', 'last_name')
                ])
        )
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('created_at_start', 'createdAtStart'),
                AllowedFilter::scope('created_at_end', 'createdAtEnd'),
            ])
            ->allowedSorts([
                'id',
                'timeoff_quota_id',
                'user_id',
                'old_balance',
                'new_balance',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $data =  TimeoffQuotaHistory::tenanted()->where('id', $id)
            ->with([
                'timeoffQuota' => fn($q) => $q->withTrashed()->select('id', 'timeoff_policy_id')->with('timeoffPolicy', fn($q) => $q->withTrashed()->select('id', 'name')),
                'user' => fn($q) => $q->select('id', 'name', 'last_name'),
                'createdBy' => fn($q) => $q->select('id', 'name', 'last_name'),
                'updatedBy' => fn($q) => $q->select('id', 'name', 'last_name')
            ])->firstOrFail();

        return new DefaultResource($data);
    }

    // public function store(StoreRequest $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $timeoffQuotaHistory = TimeoffQuotaHistory::create($request->validated());

    //         $timeoffQuotaHistory->timeoffQuotaHistoryHistories()->create([
    //             'user_id' => $timeoffQuotaHistory->user_id,
    //             'is_increment' => true,
    //             'new_balance' => $timeoffQuotaHistory->quota,
    //             'description' => $request->description,
    //         ]);
    //         DB::commit();
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return $this->errorResponse($e->getMessage());
    //     }

    //     return new DefaultResource($timeoffQuotaHistory);
    // }

    // public function update(int $id, UpdateRequest $request)
    // {
    //     $timeoffQuotaHistory = TimeoffQuotaHistory::findTenanted($id);
    //     $oldBalance = $timeoffQuotaHistory->balance;
    //     $newBalance = (float)$request->quota;

    //     DB::beginTransaction();
    //     try {
    //         $timeoffQuotaHistory->update($request->validated());

    //         $timeoffQuotaHistory->timeoffQuotaHistoryHistories()->create([
    //             'user_id' => $timeoffQuotaHistory->user_id,
    //             'is_increment' => boolval($newBalance >= $oldBalance),
    //             'old_balance' => $oldBalance,
    //             'new_balance' => $newBalance,
    //             'description' => $request->description,
    //         ]);
    //         DB::commit();
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return $this->errorResponse($e->getMessage());
    //     }

    //     return new DefaultResource($timeoffQuotaHistory);
    // }

    // public function destroy(int $id)
    // {
    //     $timeoffQuotaHistory = TimeoffQuotaHistory::findTenanted($id);
    //     $timeoffQuotaHistory->delete();

    //     return $this->deletedResponse();
    // }

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
