<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PayrollComponent\StoreRequest;
use App\Http\Requests\Api\PayrollComponent\UpdateRequest;
use App\Http\Resources\PayrollComponent\PayrollComponentResource;
use App\Models\PayrollComponent;
use App\Services\FormulaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PayrollComponentController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:payroll_component_access', ['only' => ['restore']]);
        $this->middleware('permission:payroll_component_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:payroll_component_create', ['only' => 'store']);
        $this->middleware('permission:payroll_component_edit', ['only' => 'update']);
        $this->middleware('permission:payroll_component_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(PayrollComponent::tenanted()->where('is_hidden', false))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('type'),
                AllowedFilter::scope('has_formulas'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'name', 'type', 'amount', 'is_taxable', 'period_type', 'is_monthly_prorate', 'is_daily_default', 'daily_maximum_amount_type', 'daily_maximum_amount', 'is_one_time_bonus', 'created_at',
            ])
            ->paginate($this->per_page);

        return PayrollComponentResource::collection($data);
    }

    public function show(PayrollComponent $payrollComponent): PayrollComponentResource
    {
        return new PayrollComponentResource($payrollComponent);
    }

    public function store(StoreRequest $request): PayrollComponentResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $payrollComponent = PayrollComponent::create($request->validated());

            if ($request->includes) {
                foreach ($request->includes as $include) {
                    $payrollComponent->includes()->create([
                        'included_payroll_component_id' => $include['payroll_component_id'],
                        'type' => $include['type'],
                    ]);
                }
            }

            FormulaService::sync($payrollComponent, $request->formulas);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new PayrollComponentResource($payrollComponent->refresh());
    }

    public function update(PayrollComponent $payrollComponent, UpdateRequest $request): PayrollComponentResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $payrollComponent->update($request->validated());

            $payrollComponent->includes()->delete();
            if ($request->includes) {
                foreach ($request->includes as $include) {
                    $payrollComponent->includes()->create([
                        'included_payroll_component_id' => $include['payroll_component_id'],
                        'type' => $include['type'],
                    ]);
                }
            }

            FormulaService::sync($payrollComponent, $request->formulas);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new PayrollComponentResource($payrollComponent->refresh()))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(PayrollComponent $payrollComponent): JsonResponse
    {
        try {
            // sync with empty data []
            $payrollComponent->includes()->delete();
            FormulaService::sync($payrollComponent, []);

            // delete payroll component
            $payrollComponent->delete();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }
}
