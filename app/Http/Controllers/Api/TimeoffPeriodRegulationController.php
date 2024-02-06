<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\TimeoffPeriodRegulation\StoreRequest;
use App\Http\Resources\TimeoffPeriodRegulation\TimeoffPeriodRegulationResource;
use App\Models\TimeoffPeriodRegulation;
use App\Models\TimeoffRegulation;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffPeriodRegulationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:timeoff_period_regulation_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:timeoff_period_regulation_access', ['only' => ['restore']]);
        $this->middleware('permission:timeoff_period_regulation_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:timeoff_period_regulation_create', ['only' => 'store']);
        $this->middleware('permission:timeoff_period_regulation_edit', ['only' => 'update']);
        $this->middleware('permission:timeoff_period_regulation_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(TimeoffRegulation $timeoffRegulation)
    {
        $data = QueryBuilder::for(TimeoffPeriodRegulation::where('timeoff_regulation_id', $timeoffRegulation->id))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('timeoff_regulation_id'),
            ])
            ->allowedIncludes(['timeoffRegulation'])
            ->allowedSorts([
                'id', 'timeoff_regulation_id', 'created_at'
            ])
            ->paginate($this->per_page);

        return TimeoffPeriodRegulationResource::collection($data);
    }

    public function show(TimeoffRegulation $timeoffRegulation, TimeoffPeriodRegulation $period)
    {
        return new TimeoffPeriodRegulationResource($period);
    }

    public function store(TimeoffRegulation $timeoffRegulation, StoreRequest $request)
    {
        $period = $timeoffRegulation->timeoffPeriodRegulations()->create($request->validated());

        return new TimeoffPeriodRegulationResource($period);
    }

    public function update(TimeoffRegulation $timeoffRegulation, TimeoffPeriodRegulation $period, StoreRequest $request)
    {
        $period->update($request->validated());

        return (new TimeoffPeriodRegulationResource($period))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(TimeoffRegulation $timeoffRegulation, TimeoffPeriodRegulation $period)
    {
        $period->delete();
        return $this->deletedResponse();
    }

    public function forceDelete(TimeoffRegulation $timeoffRegulation, $id)
    {
        $period = TimeoffPeriodRegulation::withTrashed()->findOrFail($id);
        $period->forceDelete();
        return $this->deletedResponse();
    }

    public function restore(TimeoffRegulation $timeoffRegulation, $id)
    {
        $period = TimeoffPeriodRegulation::withTrashed()->findOrFail($id);
        $period->restore();
        return new TimeoffPeriodRegulationResource($period);
    }
}
