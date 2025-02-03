<?php

namespace App\Http\Controllers\Api;

use App\Enums\TimeoffPolicyType;
use App\Http\Requests\Api\TimeoffQuota\StoreRequest;
use App\Http\Requests\Api\TimeoffQuota\UpdateRequest;
use App\Http\Requests\Api\TimeoffQuota\UserTimeoffQuota;
use App\Http\Resources\DefaultResource;
use App\Imports\ImportTimeoffQuotaImport;
use App\Models\Timeoff;
use App\Models\TimeoffPolicy;
use App\Models\TimeoffQuota;
use App\Models\TimeoffQuotaHistory;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
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

    public function getUserTimeoffPolicyQuota(int $userId)
    {
        if ($userId != auth()->id()) {
            User::select('id')->tenanted(true)->where('id', $userId)->firstOrFail();
        }

        // $data = TimeoffQuota::select('timeoff_policy_id', DB::raw('SUM(quota - used_quota) as remaining_balance'))
        //     ->with('timeoffPolicy', fn($q) => $q->select('id', 'name', 'code'))
        //     ->where('user_id', $userId)
        //     ->whereHas('timeoffPolicy', fn($q) => $q->whereIn('type', TimeoffPolicyType::hasQuotas()))
        //     ->groupBy('timeoff_policy_id')
        //     ->paginate();

        $data = TimeoffPolicy::select('id', 'name', 'code')
            ->tenanted()
            ->whereIn('type', TimeoffPolicyType::hasQuotas())
            ->paginate()
            ->through(function ($timeoffPolicy) use ($userId) {
                $remainingBalance = TimeoffQuota::select(DB::raw('SUM(quota - used_quota) as remaining_balance'))
                    ->where('user_id', $userId)
                    ->where('timeoff_policy_id', $timeoffPolicy->id)
                    ->first();
                $timeoffPolicy->remaining_balance = (float) $remainingBalance?->remaining_balance ?? 0;
                return $timeoffPolicy;
            });

        return DefaultResource::collection($data);
    }

    public function getUserTimeoffPolicyQuotaHistories(int $userId, int $timeoffPolicyId)
    {
        if ($userId != auth()->id()) {
            User::select('id')->tenanted()->where('id', $userId)->firstOrFail();
        }

        $adjustments = TimeoffQuotaHistory::where('user_id', $userId)
            ->whereHas('timeoffQuota', fn($q) => $q->where('timeoff_policy_id', $timeoffPolicyId))
            ->orderByDesc('id')
            ->get();

        $totalAdjusment = $adjustments->sum('balance');

        $expired = TimeoffQuota::where('user_id', $userId)
            ->where('timeoff_policy_id', $timeoffPolicyId)
            ->whereExpired()
            ->whereRaw('quota > used_quota')
            ->orderByDesc('id')
            ->get();

        $timeoffTaken = Timeoff::where('user_id', $userId)
            ->where('timeoff_policy_id', $timeoffPolicyId)
            ->approved()
            ->orderByDesc('id')
            ->get();

        $data = [
            'adjustments' => [
                'total' => $totalAdjusment,
                'data' => $adjustments
            ],
            'expired' => [
                'total' => $expired->sum('balance'),
                'data' => $expired
            ],
            'timeoff_taken' => [
                'total' => $timeoffTaken->sum('total_days'),
                'data' => $timeoffTaken
            ],
        ];

        return DefaultResource::collection($data);

        // $query = TimeoffQuotaHistory::where('user_id', $userId)
        //     ->whereHas('timeoffQuota', fn($q) => $q->where('timeoff_policy_id', $timeoffPolicyId));

        // $data = QueryBuilder::for($query)
        //     ->allowedFilters([
        //         AllowedFilter::callback('created_year', fn($query, string $value) => $query->whereYear('created', $value)),
        //     ])
        //     ->allowedSorts([
        //         'id',
        //         'is_increment',
        //         'old_balance',
        //         'new_balance',
        //         'created_at',
        //     ])
        //     ->paginate($this->per_page);
        // return UserTimeoffPolicyQuotaHistories::collection($data);
    }

    public function users(UserTimeoffQuota $request)
    {

        $data = QueryBuilder::for(
            User::select(['id', 'branch_id', 'name', 'last_name', 'nik'])
                ->tenanted()
                ->with('branch', fn($q) => $q->select('id', 'name'))
                ->where('id', 15)
            // ->with('timeoffQuotas', function ($q) {
            //     $q->whereActive()
            //         ->select('user_id', 'timeoff_policy_id', DB::raw('SUM(quota) as total_quota'))
            //         ->groupBy('user_id', 'timeoff_policy_id');
            // })
        )
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::scope('search', 'whereName'),
            ])
            ->allowedSorts([
                'id',
                'branch_id',
                'name',
            ])
            ->paginate($this->per_page);


        $companyId = $request->filter['company_id'] ?? auth()->user()->company_id;
        $timeoffPolicyIds = [];
        if (isset($request->filter['timeoff_policy_ids'])) {
            $timeoffPolicyIds = explode(',', trim($request->filter['timeoff_policy_ids']));
        }

        $timeoffPolicies = TimeoffPolicy::tenanted()
            ->whereIn('id', $timeoffPolicyIds)
            ->whereIn('type', TimeoffPolicyType::hasQuotas())
            ->where('company_id', $companyId)
            ->get(['id', 'type', 'name', 'code']);

        $data->map(function (User $user) use ($timeoffPolicies) {
            $userTimeoffPolicies = collect([]);

            foreach ($timeoffPolicies as $t) {
                $timeoffPolicy = TimeoffPolicy::select(['id', 'type', 'name', 'code'])
                    ->where('id', $t->id)
                    ->withSum([
                        'timeoffQuotas as total_quota' => fn($q) => $q->where('user_id', $user->id)->whereActive(),
                    ], DB::raw('quota - used_quota'))
                    ->first();

                if (!$timeoffPolicy) {
                    $timeoffPolicy = $t;
                }

                $timeoffPolicy->total_quota = $timeoffPolicy->total_quota ?? 0;

                $userTimeoffPolicies->push($timeoffPolicy);
            }

            $user->setRelation('timeoff_policies', $userTimeoffPolicies);
        });

        return DefaultResource::collection($data);
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

    public function destroy(int $id)
    {
        $timeoffQuota = TimeoffQuota::findTenanted($id);
        $timeoffQuota->delete();

        return $this->deletedResponse();
    }

    public function importTimeoffQuota(Request $request)
    {
        $user = auth()->user();
        if (!$user->is_super_admin) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400, "Jangan macam macam!!!");
        }

        (new ImportTimeoffQuotaImport)->import($request->file);
        return "DONE BANG";
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
