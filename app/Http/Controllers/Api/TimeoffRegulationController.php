<?php

namespace App\Http\Controllers\Api;

use App\Enums\TimeoffRenewType;
use App\Http\Requests\Api\TimeoffRegulation\StoreRequest;
use App\Http\Resources\TimeoffRegulation\TimeoffRegulationResource;
use App\Models\Company;
use App\Models\TimeoffRegulation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TimeoffRegulationController extends BaseController
{
    private ?TimeoffRegulation $timeoffRegulation;

    public function __construct(protected Company $company)
    {
        parent::__construct();
        $this->timeoffRegulation = TimeoffRegulation::tenanted()->where('company_id', request()->segment(3))->first();

        $this->middleware('permission:timeoff_regulation_read', ['only' => 'index']);
        $this->middleware('permission:timeoff_regulation_create', ['only' => 'store']);
        $this->middleware('permission:timeoff_regulation_edit', ['only' => 'update']);
    }

    public function index(Company $company)
    {
        return new TimeoffRegulationResource($this->timeoffRegulation);
    }

    public function store(Company $company, StoreRequest $request)
    {
        $timeoffRegulation = $this->timeoffRegulation;
        if ($timeoffRegulation) {
            return $this->errorResponse('Timeoff already exist', code: Response::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {
            $timeoffRegulation = $company->timeoffRegulation()->create($request->validated());
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new TimeoffRegulationResource($timeoffRegulation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function update(Company $company, StoreRequest $request)
    {
        $timeoffRegulation = $this->timeoffRegulation;
        if (!$timeoffRegulation) {
            return $this->errorResponse('Timeoff Regulation not found', code: Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();
        try {
            $timeoffRegulation->update($request->validated());
            if (!$timeoffRegulation->renew_type->is(TimeoffRenewType::MONTHLY)) {
                $timeoffRegulation->timeoffPeriodRegulations->each->timeoffRegulationMonths->each->delete();
            }
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new TimeoffRegulationResource($timeoffRegulation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
