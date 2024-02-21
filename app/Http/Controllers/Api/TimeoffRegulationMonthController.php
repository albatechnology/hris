<?php

namespace App\Http\Controllers\Api;

use App\Enums\TimeoffRenewType;
use App\Http\Requests\Api\TimeoffRegulationMonth\StoreRequest;
use App\Http\Resources\TimeoffRegulationMonth\TimeoffRegulationMonthResource;
use App\Models\Company;
use App\Models\TimeoffPeriodRegulation;
use App\Models\TimeoffRegulation;
use App\Models\TimeoffRegulationMonth;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffRegulationMonthController extends BaseController
{
    private TimeoffPeriodRegulation $timeoffPeriodRegulation;

    public function __construct()
    {
        parent::__construct();

        $timeoffRegulation = TimeoffRegulation::tenanted()->where('company_id', request()->segment(3))->firstOrFail();
        if (! $timeoffRegulation->renew_type->is(TimeoffRenewType::MONTHLY)) {
            return $this->errorResponse('Timeoff Regulation not found', code: Response::HTTP_NOT_FOUND);
        }

        $this->timeoffPeriodRegulation = $timeoffRegulation->timeoffPeriodRegulations()->findOrFail(request()->segment(6));

        // $this->middleware('permission:timeoff_regulation_month_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:timeoff_regulation_month_access', ['only' => ['restore']]);
        $this->middleware('permission:timeoff_regulation_month_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:timeoff_regulation_month_create', ['only' => 'store']);
        $this->middleware('permission:timeoff_regulation_month_edit', ['only' => 'update']);
        $this->middleware('permission:timeoff_regulation_month_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(Company $company, TimeoffPeriodRegulation $period)
    {
        $data = QueryBuilder::for($this->timeoffPeriodRegulation->timeoffRegulationMonths())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'month',
            ])
            ->allowedIncludes(['timeoffPeriodRegulation'])
            ->allowedSorts([
                'id', 'month', 'amount', 'created_at',
            ])
            ->paginate($this->per_page);

        return TimeoffRegulationMonthResource::collection($data);
    }

    public function show(Company $company, TimeoffPeriodRegulation $period, TimeoffRegulationMonth $month)
    {
        $month = $this->timeoffPeriodRegulation->timeoffRegulationMonths()->findOrFail($month->id);

        return new TimeoffRegulationMonthResource($month);
    }

    public function update(Company $company, TimeoffPeriodRegulation $period, TimeoffRegulationMonth $month, StoreRequest $request)
    {
        $month = $this->timeoffPeriodRegulation->timeoffRegulationMonths()->findOrFail($month->id);
        $month->update($request->validated());

        return (new TimeoffRegulationMonthResource($month))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
