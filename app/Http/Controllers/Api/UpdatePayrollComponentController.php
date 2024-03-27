<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UpdatePayrollComponent\StoreRequest;
use App\Http\Requests\Api\UpdatePayrollComponent\UpdateRequest;
use App\Http\Resources\UpdatePayrollComponent\UpdatePayrollComponentResource;
use App\Models\UpdatePayrollComponent;
use App\Services\FormulaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UpdatePayrollComponentController extends BaseController
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
        $data = QueryBuilder::for(UpdatePayrollComponent::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('transaction_id'),
                AllowedFilter::exact('description'),
                AllowedFilter::exact('effective_date'),
                AllowedFilter::exact('end_date'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'company_id', 'transaction_id', 'type', 'description', 'effective_date', 'end_date', 'backpay_date', 'created_by', 'updated_by',
            ])
            ->paginate($this->per_page);

        return UpdatePayrollComponentResource::collection($data);
    }

    public function show(UpdatePayrollComponent $updatePayrollComponent): UpdatePayrollComponentResource
    {
        return new UpdatePayrollComponentResource($updatePayrollComponent->load(['details.user', 'details.payrollComponent']));
    }

    public function store(StoreRequest $request): UpdatePayrollComponentResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $updatePayrollComponent = UpdatePayrollComponent::create($request->validated());
            $updatePayrollComponent->details()->createMany($request->details);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new UpdatePayrollComponentResource($updatePayrollComponent->load(['details.user', 'details.payrollComponent']));
    }

    public function update(UpdatePayrollComponent $updatePayrollComponent, UpdateRequest $request): UpdatePayrollComponentResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $updatePayrollComponent->update($request->validated());

            $updatePayrollComponent->details()->delete();
            $updatePayrollComponent->details()->createMany($request->details);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new UpdatePayrollComponentResource($updatePayrollComponent->load(['details.user', 'details.payrollComponent'])->refresh()))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
