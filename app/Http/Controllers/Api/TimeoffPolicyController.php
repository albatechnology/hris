<?php

namespace App\Http\Controllers\Api;

use App\Enums\TimeoffPolicyType;
use App\Http\Requests\Api\TimeoffPolicy\StoreRequest;
use App\Http\Resources\TimeoffPolicy\TimeoffPolicyResource;
use App\Models\TimeoffPolicy;
use Exception;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffPolicyController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:timeoff_policy_access', ['only' => ['restore']]);
        $this->middleware('permission:timeoff_policy_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:timeoff_policy_create', ['only' => 'store']);
        $this->middleware('permission:timeoff_policy_edit', ['only' => 'update']);
        $this->middleware('permission:timeoff_policy_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(TimeoffPolicy::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::scope('start_effective_date'),
                AllowedFilter::scope('end_effective_date'),
                AllowedFilter::callback('has_quota', function ($query, bool $value) {
                    if ($value == true) {
                        $query->whereNotIn('type', TimeoffPolicyType::hasQuotas())
                            ->orWhereHas('timeoffQuotas', fn($q) => $q->where('user_id', auth()->id())->whereActive());
                    }
                }),
                'type',
                'name',
                'code',
                'is_allow_halfday',
                'is_for_all_user',
                'is_enable_block_leave',
                'is_unlimited_day',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'effective_date',
                'expired_date',
                'type',
                'name',
                'code',
                'is_allow_halfday',
                'is_for_all_user',
                'is_enable_block_leave',
                'is_unlimited_day',
                'created_at',
            ])
            ->paginate($this->per_page);

        return TimeoffPolicyResource::collection($data);
    }

    public function show(TimeoffPolicy $timeoffPolicy)
    {
        $data = QueryBuilder::for(TimeoffPolicy::findTenanted($timeoffPolicy->id))
            ->allowedIncludes(['company'])
            ->firstOrFail();

        return new TimeoffPolicyResource($data);
    }

    public function store(StoreRequest $request)
    {
        try {
            $timeoffPolicy = TimeoffPolicy::create($request->validated());

            if ($request->user_ids && count($request->user_ids) > 0) {
                $timeoffPolicy->users()->sync($request->user_ids);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new TimeoffPolicyResource($timeoffPolicy);
    }

    public function update(TimeoffPolicy $timeoffPolicy, StoreRequest $request)
    {
        try {
            $timeoffPolicy->update($request->validated());

            if ($request->user_ids && count($request->user_ids) > 0) {
                $timeoffPolicy->users()->sync($request->user_ids);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new TimeoffPolicyResource($timeoffPolicy))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(TimeoffPolicy $timeoffPolicy)
    {
        $timeoffPolicy->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $timeoffPolicy = TimeoffPolicy::withTrashed()->findOrFail($id);
        $timeoffPolicy->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $timeoffPolicy = TimeoffPolicy::withTrashed()->findOrFail($id);
        $timeoffPolicy->restore();

        return new TimeoffPolicyResource($timeoffPolicy);
    }
}
