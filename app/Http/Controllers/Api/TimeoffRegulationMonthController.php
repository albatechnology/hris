<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\TimeoffRegulationMonth\StoreRequest;
use App\Http\Resources\TimeoffRegulationMonth\TimeoffRegulationMonthResource;
use App\Models\TimeoffPeriodRegulation;
use App\Models\TimeoffRegulationMonth;
use App\Models\TimeoffRegulation;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffRegulationMonthController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:timeoff_regulation_month_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:timeoff_regulation_month_access', ['only' => ['restore']]);
        $this->middleware('permission:timeoff_regulation_month_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:timeoff_regulation_month_create', ['only' => 'store']);
        $this->middleware('permission:timeoff_regulation_month_edit', ['only' => 'update']);
        $this->middleware('permission:timeoff_regulation_month_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(TimeoffRegulation $timeoffRegulation, TimeoffPeriodRegulation $period)
    {
        $data = QueryBuilder::for(TimeoffRegulationMonth::where('timeoff_period_regulation_id', $period->id))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('timeoff_period_regulation_id'),
            ])
            ->allowedIncludes(['timeoffPeriodRegulation'])
            ->allowedSorts([
                'id', 'timeoff_period_regulation_id', 'created_at'
            ])
            ->paginate($this->per_page);

        return TimeoffRegulationMonthResource::collection($data);
    }

    public function show(TimeoffRegulation $timeoffRegulation, TimeoffPeriodRegulation $period, TimeoffRegulationMonth $month)
    {
        return new TimeoffRegulationMonthResource($month);
    }

    public function store(TimeoffRegulation $timeoffRegulation, TimeoffPeriodRegulation $period, StoreRequest $request)
    {
        $month = $period->timeoffRegulationMonths()->create($request->validated());

        return new TimeoffRegulationMonthResource($month);
    }

    public function update(TimeoffRegulation $timeoffRegulation, TimeoffPeriodRegulation $period, TimeoffRegulationMonth $month, StoreRequest $request)
    {
        $month->update($request->validated());

        return (new TimeoffRegulationMonthResource($period))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(TimeoffRegulation $timeoffRegulation, TimeoffPeriodRegulation $period, TimeoffRegulationMonth $month)
    {
        $month->delete();
        return $this->deletedResponse();
    }

    public function forceDelete(TimeoffRegulation $timeoffRegulation, TimeoffPeriodRegulation $period, $id)
    {
        $month = TimeoffRegulationMonth::withTrashed()->findOrFail($id);
        $month->forceDelete();
        return $this->deletedResponse();
    }

    public function restore(TimeoffRegulation $timeoffRegulation, TimeoffPeriodRegulation $period, $id)
    {
        $month = TimeoffRegulationMonth::withTrashed()->findOrFail($id);
        $month->restore();
        return new TimeoffRegulationMonthResource($month);
    }
}
