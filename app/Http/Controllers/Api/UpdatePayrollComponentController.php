<?php

namespace App\Http\Controllers\Api;

use App\Enums\PayrollComponentCategory;
use App\Http\Requests\Api\UpdatePayrollComponent\StoreRequest;
use App\Http\Requests\Api\UpdatePayrollComponent\UpdateRequest;
use App\Http\Resources\UpdatePayrollComponent\UpdatePayrollComponentResource;
use App\Models\PayrollComponent;
use App\Models\UpdatePayrollComponent;
use App\Models\User;
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
        $data = QueryBuilder::for(UpdatePayrollComponent::tenanted()->with([
            'firstDetail.payrollComponent',
            'details' => function ($q) {
                $q->selectRaw('update_payroll_component_id')->groupByRaw('update_payroll_component_id, payroll_component_id');
            }
        ]))->allowedFilters([
            AllowedFilter::exact('company_id'),
            AllowedFilter::exact('transaction_id'),
            AllowedFilter::exact('description'),
            AllowedFilter::exact('effective_date'),
            AllowedFilter::exact('end_date'),
        ])
            ->allowedIncludes(['details.user', 'details.payrollComponent'])
            ->allowedSorts([
                'company_id',
                'transaction_id',
                'type',
                'description',
                'effective_date',
                'end_date',
                'backpay_date',
                'created_by',
                'updated_by',
            ])
            ->paginate($this->per_page);

        return UpdatePayrollComponentResource::collection($data);
    }

    public function show(int $id): UpdatePayrollComponentResource
    {
        $updatePayrollComponent = UpdatePayrollComponent::findTenanted($id);
        return new UpdatePayrollComponentResource($updatePayrollComponent->load(['details.user', 'details.payrollComponent']));
    }

    public function store(StoreRequest $request): UpdatePayrollComponentResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $updatePayrollComponent = UpdatePayrollComponent::create($request->validated());
            $updatePayrollComponent->details()->createMany($request->details);

            foreach ($request->details as $detail) {
                $user = User::find($detail['user_id']);
                $payrollComponent = PayrollComponent::find($detail['payroll_component_id']);

                if ($payrollComponent->category->is(PayrollComponentCategory::BASIC_SALARY)) $user->payrollInfo()->update(['basic_salary' => $detail['new_amount']]);
                if ($payrollComponent->category->is(PayrollComponentCategory::BPJS_KESEHATAN)) $user->userBpjs()->update(['upah_bpjs_kesehatan' => $detail['new_amount']]);
                if ($payrollComponent->category->is(PayrollComponentCategory::BPJS_KETENAGAKERJAAN)) $user->userBpjs()->update(['upah_bpjs_ketenagakerjaan' => $detail['new_amount']]);
            }

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new UpdatePayrollComponentResource($updatePayrollComponent->load(['details.user', 'details.payrollComponent']));
    }

    public function update(int $id, UpdateRequest $request): UpdatePayrollComponentResource|JsonResponse
    {
        $updatePayrollComponent = UpdatePayrollComponent::findTenanted($id);
        DB::beginTransaction();
        try {
            $updatePayrollComponent->update($request->validated());

            $updatePayrollComponent->details()->delete();
            $updatePayrollComponent->details()->createMany($request->details);

            foreach ($request->details as $detail) {
                $user = User::find($detail['user_id']);
                $payrollComponent = PayrollComponent::find($detail['payroll_component_id']);

                if ($payrollComponent->category->is(PayrollComponentCategory::BASIC_SALARY)) $user->payrollInfo()->update(['basic_salary' => $detail['new_amount']]);
                if ($payrollComponent->category->is(PayrollComponentCategory::BPJS_KESEHATAN)) $user->userBpjs()->update(['upah_bpjs_kesehatan' => $detail['new_amount']]);
                if ($payrollComponent->category->is(PayrollComponentCategory::BPJS_KETENAGAKERJAAN)) $user->userBpjs()->update(['upah_bpjs_ketenagakerjaan' => $detail['new_amount']]);
            }

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new UpdatePayrollComponentResource($updatePayrollComponent->load(['details.user', 'details.payrollComponent'])->refresh()))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id): JsonResponse
    {
        $updatePayrollComponent = UpdatePayrollComponent::findTenanted($id);
        DB::beginTransaction();
        try {
            $updatePayrollComponent->details()->delete();
            $updatePayrollComponent->delete();

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }
}
