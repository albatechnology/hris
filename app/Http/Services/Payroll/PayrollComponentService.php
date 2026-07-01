<?php

namespace App\Http\Services\Payroll;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Payroll\PayrollComponentRepositoryInterface;
use App\Interfaces\Services\Payroll\PayrollComponentServiceInterface;
use App\Models\PayrollComponent;
use App\Services\FormulaService;
use Exception;
use Illuminate\Support\Facades\DB;

class PayrollComponentService extends BaseService implements PayrollComponentServiceInterface
{
    public function __construct(protected PayrollComponentRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): PayrollComponent
    {
        DB::beginTransaction();
        try {
            $payrollComponent = $this->repository->create($data);

            if (isset($data['includes'])) {
                foreach ($data['includes'] as $include) {
                    $payrollComponent->includes()->create([
                        'included_payroll_component_id' => $include['payroll_component_id'],
                        'type' => $include['type'],
                    ]);
                }
            }

            if (isset($data['formulas'])) {
                FormulaService::sync($payrollComponent, $data['formulas']);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $payrollComponent;
    }

    public function update(string $id, array $data): bool
    {
        $payrollComponent = $this->repository->findById($id);

        DB::beginTransaction();
        try {
            $this->repository->update($id, $data);

            $payrollComponent->includes()->delete();
            if (isset($data['includes'])) {
                foreach ($data['includes'] as $include) {
                    $payrollComponent->includes()->create([
                        'included_payroll_component_id' => $include['payroll_component_id'],
                        'type' => $include['type'],
                    ]);
                }
            }

            if (isset($data['formulas'])) {
                FormulaService::sync($payrollComponent, $data['formulas']);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return true;
    }

    public function delete(string $id): bool
    {
        $payrollComponent = $this->repository->findById($id);

        try {
            $payrollComponent->update(['name' => $payrollComponent->name . '-deleted-' . date('YmdHis')]);
            // sync with empty data []
            $payrollComponent->includes()->delete();
            FormulaService::sync($payrollComponent, []);

            // delete payroll component
            $payrollComponent->delete();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return true;
    }

    public function forceDelete(string $id): bool
    {
        $payrollComponent = $this->repository->findById($id, fn($q) => $q->withTrashed());

        $payrollComponent->includes()->delete();
        FormulaService::sync($payrollComponent, []);

        return $this->repository->forceDelete($id);
    }
}