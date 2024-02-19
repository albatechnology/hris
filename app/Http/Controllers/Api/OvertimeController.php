<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Overtime\StoreRequest;
use App\Http\Requests\Api\Overtime\UpdateRequest;
use App\Http\Requests\Api\Overtime\UserSettingRequest;
use App\Http\Resources\Overtime\OvertimeResource;
use App\Models\Overtime;
use App\Models\OvertimeFormula;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    public function index(): ResourceCollection
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

    public function show(Overtime $overtime): OvertimeResource
    {
        $data = QueryBuilder::for(Overtime::findTenanted($overtime->id))->allowedIncludes(['company'])->firstOrFail();

        return new OvertimeResource($data);
    }

    public static function syncOvertimeFormula(Overtime $overtime, array $overtimeFormulas, OvertimeFormula $parent = null)
    {
        foreach ($overtimeFormulas as $overtimeFormula) {
            if (isset($overtimeFormula['child']) && is_array($overtimeFormula['child'])) {
                $newOvertimeFormula = $overtime->overtimeFormulas()->create([
                    'parent_id' => $parent->id ?? null,
                    'component' => $overtimeFormula['component'],
                    'amount' => $overtimeFormula['amount'] ?? null,
                ]);

                self::syncOvertimeFormula($overtime, $overtimeFormula['child'], $newOvertimeFormula);
            } else {
                $newOvertimeFormula = $overtime->overtimeFormulas()->create([
                    'parent_id' => $parent->id ?? null,
                    'component' => $overtimeFormula['component'],
                    'amount' => $overtimeFormula['amount'],
                ]);
            }

            collect(explode(",", $overtimeFormula['value']))->each(function ($overtimeFormulaValue) use ($newOvertimeFormula) {
                $newOvertimeFormula->overtimeFormulaComponents()->create([
                    'value' => $overtimeFormulaValue
                ]);
            });
        }
    }

    public function store(StoreRequest $request): OvertimeResource | JsonResponse
    {
        // check correct order for overtime rounding hours
        collect($request->overtime_roundings)->each(function ($overtimeRounding, $i) use ($request) {
            if ($i > 0 && $overtimeRounding['start_minute'] <= $request->overtime_roundings[$i - 1]['end_minute']) {
                return $this->errorResponse('start_minute and end_minute between the overtime_roundings are not in the correct order, please check it first');
            }
        });

        // check correct order for overtime multiplier hours
        collect($request->overtime_multipliers)->each(function ($overtimeMultiplier, $i) use ($request) {
            if ($i > 0 && $overtimeMultiplier['start_hour'] <= $request->overtime_multipliers[$i - 1]['end_hour']) {
                return $this->errorResponse('start_hour and end_hour between the overtime_multipliers are not in the correct order, please check it first');
            }
        });

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

            if ($request->overtime_roundings) $overtime->overtimeRoundings()->createMany($request->overtime_roundings);
            if ($request->overtime_multiplier) $overtime->overtimeMultiplier()->createMany($request->overtime_multiplier);
            if ($request->overtime_allowances) $overtime->overtimeAllowances()->createMany($request->overtime_allowances);
            if ($request->overtime_formulas) self::syncOvertimeFormula($overtime, $request->overtime_formulas);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeResource($overtime);
    }

    public function update(Overtime $overtime, UpdateRequest $request): OvertimeResource | JsonResponse
    {
        // check correct order for overtime rounding hours
        collect($request->overtime_roundings)->each(function ($overtimeRounding, $i) use ($request) {
            if ($i > 0 && $overtimeRounding['start_minute'] <= $request->overtime_roundings[$i - 1]['end_minute']) {
                return $this->errorResponse('start_minute and end_minute between the overtime_roundings are not in the correct order, please check it first');
            }
        });

        // check correct order for overtime multiplier hours
        collect($request->overtime_multipliers)->each(function ($overtimeMultiplier, $i) use ($request) {
            if ($i > 0 && $overtimeMultiplier['start_hour'] <= $request->overtime_multipliers[$i - 1]['end_hour']) {
                return $this->errorResponse('start_hour and end_hour between the overtime_multipliers are not in the correct order, please check it first');
            }
        });

        DB::beginTransaction();
        try {
            $overtime->update([
                'company_id' => $request->company_id,
                'name' => $request->name,
                'is_rounding' => $request->is_rounding,
                'compensation_rate_per_day' => $request->compensation_rate_per_day,
                'rate_type' => $request->rate_type,
                'rate_amount' => $request->rate_amount,
            ]);

            $overtime->overtimeRoundings()->delete();
            if ($request->overtime_roundings) $overtime->overtimeRoundings()->createMany($request->overtime_roundings);

            $overtime->overtimeMultipliers()->delete();
            if ($request->overtime_multiplier) $overtime->overtimeMultiplier()->createMany($request->overtime_multiplier);

            $overtime->overtimeAllowances()->delete();
            if ($request->overtime_allowances) $overtime->overtimeAllowances()->createMany($request->overtime_allowances);

            foreach ($overtime->overtimeFormulas as $overtimeFormula) {
                Schema::disableForeignKeyConstraints();
                $overtimeFormula->overtimeFormulaComponents()->delete();
                $overtimeFormula->delete();
                Schema::enableForeignKeyConstraints();
            }
            if ($request->overtime_formulas) self::syncOvertimeFormula($overtime, $request->overtime_formulas);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return (new OvertimeResource($overtime))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Overtime $overtime): JsonResponse
    {
        try {
            Schema::disableForeignKeyConstraints();

            foreach ($overtime->overtimeFormulas as $overtimeFormula) {
                $overtimeFormula->overtimeFormulaComponents()->delete();
                $overtimeFormula->delete();
            }

            $overtime->delete();

            Schema::enableForeignKeyConstraints();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }

    public function userSetting(UserSettingRequest $request): JsonResponse
    {
        try {
            User::find($request->user_id)->update([
                'overtime_id' => $request->overtime_id,
            ]);
        } catch (\Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return $this->updatedResponse();
    }
}
