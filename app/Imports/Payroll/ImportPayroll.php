<?php

namespace App\Imports\Payroll;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentType;
use App\Enums\RunPayrollStatus;
use App\Models\PayrollComponent;
use App\Models\RunPayroll;
use App\Models\RunPayrollUser;
use App\Models\RunPayrollUserComponent;
use App\Models\User;
use Illuminate\Support\Collection;

class ImportPayroll
{
    public array $datas = [];
    private RunPayroll $runPayroll;

    public function __construct(
        private int $companyId,
        private array $runPayrollData = [],
    ) {}

    public function setRunPayrollData(array $data): self
    {
        $this->runPayrollData = $data;
        return $this;
    }

    public function collection(Collection $rows): void
    {
        // Row 0: Skip (labeling header)
        // Row 1: Component names reference
        // Row 2+: User data

        if ($rows->count() < 3) {
            return;
        }

        // Get component names from row 1 (index 1)
        $componentNamesRow = $rows[1];

        // Get all components keyed by name
        $allComponents = $this->getAllComponents();

        // Define column ranges
        $allowanceRange = range(3, 17);   // C2:L2
        $deductionRange = range(18, 32);  // M2:V2
        $benefitRange = range(33, 47);    // W2:AF2

        // Extract component names from row 1
        $componentMap = [];

        foreach ($allowanceRange as $col) {
            $name = trim($componentNamesRow[$col] ?? '');
            if ($name) {
                $componentMap[$col] = [
                    'name' => $name,
                    'type' => PayrollComponentType::ALLOWANCE,
                ];
            }
        }

        foreach ($deductionRange as $col) {
            $name = trim($componentNamesRow[$col] ?? '');
            if ($name) {
                $componentMap[$col] = [
                    'name' => $name,
                    'type' => PayrollComponentType::DEDUCTION,
                ];
            }
        }

        foreach ($benefitRange as $col) {
            $name = trim($componentNamesRow[$col] ?? '');
            if ($name) {
                $componentMap[$col] = [
                    'name' => $name,
                    'type' => PayrollComponentType::BENEFIT,
                ];
            }
        }

        // Get all users by NIK from data rows (row 2+)
        $dataRows = $rows->slice(2);
        $userNiks = $dataRows->pluck(0)->unique()->filter();
        $users = User::where('company_id', $this->companyId)
            ->whereIn('nik', $userNiks)
            ->get(['id', 'nik', 'name'])
            ->keyBy('nik');

        // Create RunPayroll
        $this->createRunPayroll();

        // Process each data row (row 2+)
        foreach ($dataRows as $row) {
            $userNik = $row[0] ?? null;
            if (!$userNik || !isset($users[$userNik])) {
                continue;
            }

            $user = $users[$userNik];
            $this->processUserRow($row, $user, $componentMap, $allComponents);
        }
    }

    private function getAllComponents(): Collection
    {
        return PayrollComponent::active()
            ->where('company_id', $this->companyId)
            ->get(['id', 'name', 'type', 'category'])
            ->keyBy('name');
    }

    private function createRunPayroll(): void
    {
        $this->runPayroll = RunPayroll::create([
            'company_id' => $this->companyId,
            'branch_id' => $this->runPayrollData['branch_id'] ?? null,
            'user_id' => auth()->id(),
            // 'code' => RunPayroll::generateCode(),
            'period' => $this->runPayrollData['period'] ?? date('Y-m'),
            'payment_schedule' => $this->runPayrollData['payment_schedule'] ?? now(),
            'status' => RunPayrollStatus::REVIEW,
            'cut_off_start_date' => $this->runPayrollData['cut_off_start_date'] ?? null,
            'cut_off_end_date' => $this->runPayrollData['cut_off_end_date'] ?? null,
            'payroll_start_date' => $this->runPayrollData['payroll_start_date'] ?? null,
            'payroll_end_date' => $this->runPayrollData['payroll_end_date'] ?? null,
        ]);
    }

    private function processUserRow(array $row, User $user, array $componentMap, Collection $allComponents): void
    {
        // Extract tax from column C (index 2)
        $tax = $row[2] ?? 0;
        $tax = is_numeric($tax) ? (float)$tax : 0;

        // Create RunPayrollUser with initial values
        $runPayrollUser = RunPayrollUser::create([
            'run_payroll_id' => $this->runPayroll->id,
            'user_id' => $user->id,
            'basic_salary' => 0,
            'gross_salary' => 0,
            'allowance' => 0,
            'additional_earning' => 0,
            'deduction' => 0,
            'benefit' => 0,
            'tax' => $tax,
        ]);
        // Process each column that has a component name
        foreach ($componentMap as $colIndex => $componentInfo) {
            $componentName = $componentInfo['name'];
            $componentType = $componentInfo['type'];
            $amount = $row[$colIndex] ?? 0;

            // Convert to numeric, default to 0
            $amount = is_numeric($amount) ? (float)$amount : 0;

            // Find component by name and type
            $component = $allComponents->first(function ($item) use ($componentName, $componentType) {
                return $item->name === $componentName && $item->type === $componentType;
            });
            // Only create if component exists in database
            if ($component) {
                RunPayrollUserComponent::create([
                    'run_payroll_user_id' => $runPayrollUser->id,
                    'payroll_component_id' => $component->id,
                    'amount' => $amount,
                    'is_editable' => true,
                    'payroll_component' => [
                        'id' => $component->id,
                        'name' => $component->name,
                        'type' => $component->type->value,
                    ],
                ]);
            }
        }

        // Calculate salary fields after all components are created
        $this->updateUserSalaryFields($runPayrollUser);

        $this->datas[] = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'run_payroll_user_id' => $runPayrollUser->id,
        ];
    }

    private function updateUserSalaryFields(RunPayrollUser $runPayrollUser): void
    {
        // Get all components for this user
        $components = $runPayrollUser->components()
            ->with('payrollComponent:id,category,type')
            ->get();

        $basicSalary = 0;
        $allowance = 0;
        $deduction = 0;
        $benefit = 0;

        foreach ($components as $component) {
            if (!$component->payrollComponent) {
                continue;
            }

            $amount = $component->amount;
            $category = $component->payrollComponent->category;
            $type = $component->payrollComponent->type;

            // basic_salary: category = BASIC_SALARY
            if ($category === PayrollComponentCategory::BASIC_SALARY) {
                $basicSalary += $amount;
            }
            // allowance: type = ALLOWANCE and category != BASIC_SALARY
            elseif ($type === PayrollComponentType::ALLOWANCE && $category !== PayrollComponentCategory::BASIC_SALARY) {
                $allowance += $amount;
            }
            // deduction: type = DEDUCTION
            elseif ($type === PayrollComponentType::DEDUCTION) {
                $deduction += $amount;
            }
            // benefit: type = BENEFIT
            elseif ($type === PayrollComponentType::BENEFIT) {
                $benefit += $amount;
            }
        }

        // Update RunPayrollUser
        $runPayrollUser->update([
            'basic_salary' => $basicSalary,
            'allowance' => $allowance,
            'deduction' => $deduction,
            'benefit' => $benefit,
        ]);
    }
}
