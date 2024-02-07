<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Overtime\StoreRequest;
use App\Http\Resources\Overtime\OvertimeResource;
use App\Models\Overtime;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\DB;

class OvertimeController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:overtime_access', ['only' => ['restore']]);
        $this->middleware('permission:overtime_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:overtime_create', ['only' => 'store']);
        $this->middleware('permission:overtime_edit', ['only' => 'update']);
        $this->middleware('permission:overtime_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Overtime::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'is_rounding', 'compensation_rate_per_day', 'rate_type', 'rate_amount', 'created_at'
            ])
            ->paginate($this->per_page);

        return OvertimeResource::collection($data);
    }

    public function show(Overtime $overtime)
    {
        $data = QueryBuilder::for(Overtime::findTenanted($overtime->id))
            ->allowedIncludes(['company'])
            ->firstOrFail();

        return new OvertimeResource($data);
    }

    public function store(StoreRequest $request)
    {
        // check correct order for overtime rounding hours
        foreach ($request->overtime_roundings as $i => $overtimeRounding) {
            if ($i > 0 && $overtimeRounding['start_minute'] <= $request->overtime_roundings[$i - 1]['end_minute']) {
                return $this->errorResponse('start_minute and end_minute between the arrays are not in the correct order, please check it first');
            }
        }

        // check correct order for overtime multiplier hours
        foreach ($request->overtime_multipliers as $i => $overtimeMultiplier) {
            if ($i > 0 && $overtimeMultiplier['start_hour'] <= $request->overtime_multipliers[$i - 1]['end_hour']) {
                return $this->errorResponse('start_hour and end_hour between the arrays are not in the correct order, please check it first');
            }
        }

        DB::beginTransaction();
        try {
            $overtime = Overtime::create([
                'company_id' => $request->company_id,
                'name' => $request->name,
                'is_rounding' => $request->is_rounding,
                'compensation_rate_per_day' => $request->compensation_rate_per_day,
                'rate_type' => $request->rate_type,
                'rate_amount' => $request->rate_amount,
            ]);

            foreach ($request->overtime_roundings as $overtimeRounding) {
                $overtime->overtimeRoundings()->create([
                    'start_minute' => $overtimeRounding['start_minute'],
                    'end_minute' => $overtimeRounding['end_minute'],
                    'rounded' => $overtimeRounding['rounded'],
                ]);
            }

            foreach ($request->overtime_multipliers as $overtimeMultiplier) {
                $overtime->overtimeMultipliers()->create([
                    'is_weekday' => $overtimeMultiplier['is_weekday'],
                    'start_hour' => $overtimeMultiplier['start_hour'],
                    'end_hour' => $overtimeMultiplier['end_hour'],
                    'multiply' => $overtimeMultiplier['multiply'],
                ]);
            }

            foreach ($request->overtime_allowances as $overtimeAllowance) {
                $overtime->overtimeAllowances()->create([
                    'amount' => $overtimeAllowance['amount'],
                ]);
            }
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeResource($overtime);
    }

    public function update(Overtime $overtime, StoreRequest $request)
    {
        $overtime->update($request->validated());

        return (new OvertimeResource($overtime))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Overtime $overtime)
    {
        $overtime->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $overtime = Overtime::withTrashed()->findOrFail($id);
        $overtime->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $overtime = Overtime::withTrashed()->findOrFail($id);
        $overtime->restore();
        return new OvertimeResource($overtime);
    }
}
