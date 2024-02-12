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
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('timeoff_policy_id'),
                AllowedFilter::exact('delegate_to'),
                AllowedFilter::scope('start_at'),
                AllowedFilter::scope('end_at'),
                'request_type'
            ])
            ->allowedIncludes(['user', 'timeoffPolicy', 'delegateTo'])
            ->allowedSorts([
                'id', 'user_id', 'timeoff_policy_id', 'delegate_to', 'start_at', 'end_at', 'request_type', 'created_at'
            ])
            ->paginate($this->per_page);

        return TimeoffResource::collection($data);
    }

    public function show(Timeoff $timeoff)
    {
        $timeoff->load(['user', 'timeoffPolicy', 'delegateTo']);

        return new TimeoffResource($timeoff);
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