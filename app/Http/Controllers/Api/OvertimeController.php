<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Overtime\StoreRequest;
use App\Http\Requests\Api\Overtime\UpdateRequest;
use App\Http\Requests\Api\Overtime\UserSettingRequest;
use App\Http\Resources\Overtime\OvertimeResource;
use App\Models\Overtime;
use App\Models\User;
use App\Services\FormulaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

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
                AllowedFilter::exact('company_id'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'is_rounding',
                'compensation_rate_per_day',
                'rate_type',
                'rate_amount',
                'created_at',
            ])
            ->paginate($this->per_page);

        return OvertimeResource::collection($data);
    }

    public function show(int $id): OvertimeResource
    {
        $overtime = Overtime::findTenanted($id);
        return new OvertimeResource($overtime);
    }

    public static function saveRelationship(int $id, Request $request)
    {
        $overtime = Overtime::findTenanted($id);
        $overtime->overtimeRoundings()->delete();
        $overtime->overtimeMultipliers()->delete();
        $overtime->overtimeAllowances()->delete();

        if ($request->overtime_roundings) {
            $overtime->overtimeRoundings()->createMany($request->overtime_roundings);
        }
        if ($request->overtime_multipliers) {
            $overtime->overtimeMultipliers()->createMany($request->overtime_multipliers);
        }
        if ($request->overtime_allowances) {
            $overtime->overtimeAllowances()->createMany($request->overtime_allowances);
        }
    }

    public function store(StoreRequest $request): OvertimeResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $overtime = Overtime::create($request->validated());

            self::saveRelationship($overtime, $request);

            FormulaService::sync($overtime, $request->formulas);
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeResource($overtime->refresh());
    }

    public function update(int $id, UpdateRequest $request): OvertimeResource|JsonResponse
    {
        $overtime = Overtime::findTenanted($id);
        DB::beginTransaction();
        try {
            $overtime->update($request->validated());

            self::saveRelationship($overtime, $request);

            FormulaService::sync($overtime, $request->formulas);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new OvertimeResource($overtime->refresh()))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id): JsonResponse
    {
        $overtime = Overtime::findTenanted($id);
        DB::beginTransaction();
        try {
            // sync formula with empty data []
            FormulaService::sync($overtime, []);

            // delete overtime
            $overtime->overtimeRoundings()->delete();
            $overtime->overtimeMultipliers()->delete();
            $overtime->overtimeAllowances()->delete();
            $overtime->delete();

            DB::commit();
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
