<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\TimeoffRegulation\StoreRequest;
use App\Http\Resources\TimeoffRegulation\TimeoffRegulationResource;
use App\Models\TimeoffRegulation;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffRegulationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:timeoff_regulation_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:timeoff_regulation_access', ['only' => ['restore']]);
        $this->middleware('permission:timeoff_regulation_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:timeoff_regulation_create', ['only' => 'store']);
        $this->middleware('permission:timeoff_regulation_edit', ['only' => 'update']);
        $this->middleware('permission:timeoff_regulation_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(TimeoffRegulation::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::scope('start_period'),
                AllowedFilter::scope('end_period'),
                'renew_type',
                'is_allow_halfday',
                'is_expired_in_end_period',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'start_period','end_period','renew_type','is_allow_halfday','is_expired_in_end_period','created_at'
            ])
            ->paginate($this->per_page);

        return TimeoffRegulationResource::collection($data);
    }

    public function show(TimeoffRegulation $timeoffRegulation)
    {
        $data = QueryBuilder::for(TimeoffRegulation::findTenanted($timeoffRegulation->id))
            ->allowedIncludes(['company'])
            ->firstOrFail();

        return new TimeoffRegulationResource($data);
    }

    public function store(StoreRequest $request)
    {
        $timeoffRegulation = TimeoffRegulation::create($request->validated());

        return new TimeoffRegulationResource($timeoffRegulation);
    }

    public function update(TimeoffRegulation $timeoffRegulation, StoreRequest $request)
    {
        $timeoffRegulation->update($request->validated());

        return (new TimeoffRegulationResource($timeoffRegulation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(TimeoffRegulation $timeoffRegulation)
    {
        $timeoffRegulation->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $timeoffRegulation = TimeoffRegulation::withTrashed()->findOrFail($id);
        $timeoffRegulation->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $timeoffRegulation = TimeoffRegulation::withTrashed()->findOrFail($id);
        $timeoffRegulation->restore();
        return new TimeoffRegulationResource($timeoffRegulation);
    }
}
