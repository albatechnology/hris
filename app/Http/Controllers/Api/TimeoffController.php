<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Timeoff\StoreRequest;
use App\Http\Resources\Timeoff\TimeoffResource;
use App\Models\Timeoff;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:timeoff_access', ['only' => ['index', 'show', 'restore']]);
        // $this->middleware('permission:timeoff_access', ['only' => ['restore']]);
        // $this->middleware('permission:timeoff_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:timeoff_create', ['only' => 'store']);
        // $this->middleware('permission:timeoff_edit', ['only' => 'update']);
        // $this->middleware('permission:timeoff_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Timeoff::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::scope('start_effective_date'),
                AllowedFilter::scope('end_effective_date'),
                'type',
                'name',
                'code',
                'is_for_all_user',
                'is_enable_block_leave',
                'is_unlimited_day'
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'effective_date', 'expired_date', 'type', 'name', 'code', 'is_for_all_user', 'is_enable_block_leave', 'is_unlimited_day', 'created_at'
            ])
            ->paginate($this->per_page);

        return TimeoffResource::collection($data);
    }

    public function show(Timeoff $timeoff)
    {
        $data = QueryBuilder::for(Timeoff::where('id', $timeoff->id))
            ->allowedIncludes(['user', 'delegateTo'])
            ->firstOrFail();

        return new TimeoffResource($data);
    }

    public function store(StoreRequest $request)
    {
        $timeoff = Timeoff::create($request->validated());

        return new TimeoffResource($timeoff);
    }

    public function update(Timeoff $timeoff, StoreRequest $request)
    {
        $timeoff->update($request->validated());

        return (new TimeoffResource($timeoff))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Timeoff $timeoff)
    {
        $timeoff->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $timeoff = Timeoff::withTrashed()->findOrFail($id);
        $timeoff->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $timeoff = Timeoff::withTrashed()->findOrFail($id);
        $timeoff->restore();
        return new TimeoffResource($timeoff);
    }
}
