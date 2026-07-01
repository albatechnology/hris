<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PayrollComponent\StoreRequest;
use App\Http\Requests\Api\PayrollComponent\UpdateRequest;
use App\Http\Resources\PayrollComponent\PayrollComponentResource;
use App\Interfaces\Services\Payroll\PayrollComponentServiceInterface;
use App\Models\PayrollComponent;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class PayrollComponentController extends BaseController
{
    public function __construct(private PayrollComponentServiceInterface $service)
    {
        parent::__construct();

        // $this->middleware('permission:payroll_setting_access', ['only' => ['restore']]);
        // $this->middleware('permission:payroll_setting_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:payroll_setting_create', ['only' => 'store']);
        // $this->middleware('permission:payroll_setting_edit', ['only' => 'update']);
        // $this->middleware('permission:payroll_setting_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    private function allowedIncludes(): array
    {
        return ['company'];
    }

    public function index(): ResourceCollection
    {
        Gate::authorize('viewAny', PayrollComponent::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->where('is_hidden', false),
            [
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::scope('has_formulas'),
                AllowedFilter::scope('available_for_update_payroll_component'),
            ],
            $this->allowedIncludes(),
            [
                'id',
                'company_id',
                'branch_id',
                'name',
                'type',
                'amount',
                'is_taxable',
                'is_prorate',
                'period_type',
                'is_monthly_prorate',
                // 'is_daily_default',
                // 'daily_maximum_amount_type',
                // 'daily_maximum_amount',
                // 'is_one_time_bonus',
                'created_at',
            ],
        );

        return PayrollComponentResource::collection($datas);
    }

    public function show(int $id): PayrollComponentResource
    {
        $data = PayrollComponent::findTenanted($id);
        return new PayrollComponentResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', PayrollComponent::class);

        $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(UpdateRequest $request, int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('forceDelete', $data);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('restore', $data);

        $this->service->restore($id);

        return $this->restoredResponse();
    }
}
