<?php

namespace App\Http\Controllers\Api;

use App\Enums\TimeoffRenewType;
use App\Http\Requests\Api\TimeoffPeriodRegulation\StoreRequest;
use App\Http\Resources\TimeoffPeriodRegulation\TimeoffPeriodRegulationResource;
use App\Models\Company;
use App\Models\TimeoffPeriodRegulation;
use App\Models\TimeoffRegulation;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffPeriodRegulationController extends BaseController
{
    private TimeoffRegulation $timeoffRegulation;

    public function __construct()
    {
        parent::__construct();

        $this->timeoffRegulation = TimeoffRegulation::tenanted()->where('company_id', request()->segment(3))->firstOrFail();

        if (!$this->timeoffRegulation->renew_type->is(TimeoffRenewType::MONTHLY)) {
            throw new \Exception('Timeoff Regulation not found', Response::HTTP_NOT_FOUND);
            // return $this->errorResponse('Timeoff Regulation not found', code: Response::HTTP_NOT_FOUND);
        }

        $this->middleware('permission:timeoff_regulation_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:timeoff_regulation_create', ['only' => 'store']);
        $this->middleware('permission:timeoff_regulation_edit', ['only' => 'update']);
        $this->middleware('permission:timeoff_regulation_delete', ['only' => 'destroy']);
    }

    public function index(Company $company)
    {
        $data = QueryBuilder::for($this->timeoffRegulation->timeoffPeriodRegulations())
            ->allowedIncludes(['timeoffRegulation', 'timeoffRegulationMonths'])
            ->allowedSorts([
                'id', 'min_working_month', 'max_working_month', 'created_at',
            ])
            ->paginate($this->per_page);

        return TimeoffPeriodRegulationResource::collection($data);
    }

    public function show(Company $company, TimeoffPeriodRegulation $period)
    {
        $period = $this->timeoffRegulation->timeoffPeriodRegulations()->findOrFail($period->id);

        return new TimeoffPeriodRegulationResource($period);
    }

    public function store(Company $company, StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            /** @var TimeoffPeriodRegulation $period */
            $period = $this->timeoffRegulation->timeoffPeriodRegulations()->create($request->validated());
            foreach ($request->month as $month => $value) {
                $period->timeoffRegulationMonths()->create([
                    'month' => $month,
                    'amount' => $value
                ]);
            }
            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new TimeoffPeriodRegulationResource($period);
    }

    public function update(Company $company, TimeoffPeriodRegulation $period, StoreRequest $request)
    {
        /** @var TimeoffPeriodRegulation $period */
        $period = $this->timeoffRegulation->timeoffPeriodRegulations()->findOrFail($period->id);

        DB::beginTransaction();
        try {
            $period->update($request->validated());

            foreach ($request->month as $month => $value) {
                $timeoffRegulationMonth = $period->timeoffRegulationMonths()->where('month', $month)->first();
                if ($timeoffRegulationMonth) {
                    $timeoffRegulationMonth->update([
                        'amount' => $value
                    ]);
                } else {
                    $period->timeoffRegulationMonths()->create([
                        'month' => $month,
                        'amount' => $value
                    ]);
                }
            }
            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return (new TimeoffPeriodRegulationResource($period))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Company $company, TimeoffPeriodRegulation $period)
    {
        $period = $this->timeoffRegulation->timeoffPeriodRegulations()->findOrFail($period->id);
        $period->delete();

        return $this->deletedResponse();
    }
}
