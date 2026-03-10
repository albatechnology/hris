<?php

namespace App\Imports\Payroll;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentType;
use App\Enums\RunPayrollStatus;
use App\Models\PayrollComponent;
use App\Models\RunThr;
use App\Models\RunThrUser;
use App\Models\RunThrUserComponent;
use App\Models\User;
use Illuminate\Support\Collection;

class ImportThr
{
    public array $datas = [];
    private RunThr $runThr;

    public function __construct(
        private int $companyId,
        private array $runThrData = [],
    ) {}

    public function setRunThrData(array $data): self
    {
        $this->runThrData = $data;
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
            ->with('payrollInfo')
            ->get(['id', 'nik', 'name'])
            ->keyBy('nik');

        // Create RunThr
        $this->createRunThr();

        // Process each data row (row 2+)
        foreach ($dataRows as $row) {
            $userNik = $row[0] ?? null;
            if (!$userNik || !isset($users[$userNik])) {
                continue;
            }

            $user = $users[$userNik];
            if (!$user || !$user->payrollInfo) {
                continue;
            }
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

    private function createRunThr(): void
    {
        $this->runThr = RunThr::create([
            'company_id' => $this->companyId,
            'user_id' => auth()->id(),
            'thr_date' => $this->runThrData['thr_date'] ?? now(),
            'payment_date' => $this->runThrData['payment_date'] ?? now(),
            'status' => RunPayrollStatus::REVIEW,
        ]);
    }

    private function processUserRow(array $row, User $user, array $componentMap, Collection $allComponents): void
    {
        // Extract tax from column C (index 2)
        $tax = $row[2] ?? 0;
        $tax = is_numeric($tax) ? (float)$tax : 0;

        // Create RunThrUser with initial values
        $runThrUser = RunThrUser::create([
            'run_thr_id' => $this->runThr->id,
            'user_id' => $user->id,
            'basic_salary' => 0,
            'gross_salary' => 0,
            'allowance' => 0,
            'additional_earning' => 0,
            'deduction' => 0,
            'benefit' => 0,
            'tax_salary' => 0,
            'total_tax_thr' => $tax,
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
                RunThrUserComponent::create([
                    'run_thr_user_id' => $runThrUser->id,
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
        $this->updateUserSalaryFields($runThrUser);

        // Refresh RunThrUser to calculate totals like in RunThrService
        // \App\Services\RunThrService::refreshRunThrUser($runThrUser);

        $this->datas[] = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'run_thr_user_id' => $runThrUser->id,
        ];
    }

    private function refreshRunThrUser(RunThrUser $runThrUser)
    {
        $basicSalary = $runThrUser->basic_salary;

        // Load all components with payrollComponent
        $components = $runThrUser->components()->with('payrollComponent:id,type,category,is_taxable,is_calculate_thr')->get();

        // Use collection to filter and sum
        $allowanceTaxable = $components->filter(function ($component) {
            return $component->payrollComponent &&
                $component->payrollComponent->type === PayrollComponentType::ALLOWANCE &&
                !in_array($component->payrollComponent->category, [PayrollComponentCategory::BASIC_SALARY]) &&
                $component->payrollComponent->is_taxable &&
                $component->payrollComponent->is_calculate_thr;
        })->sum('amount');

        $allowanceNonTaxable = $components->filter(function ($component) {
            return $component->payrollComponent &&
                $component->payrollComponent->type === PayrollComponentType::ALLOWANCE &&
                !in_array($component->payrollComponent->category, [PayrollComponentCategory::BASIC_SALARY]) &&
                !$component->payrollComponent->is_taxable &&
                $component->payrollComponent->is_calculate_thr;
        })->sum('amount');

        $additionalEarning = 0; // belum kepake

        $taxableBenefit = $components->filter(function ($component) {
            return $component->payrollComponent &&
                $component->payrollComponent->type === PayrollComponentType::BENEFIT &&
                !in_array($component->payrollComponent->category, [PayrollComponentCategory::COMPANY_JHT, PayrollComponentCategory::COMPANY_JP]);
        })->sum('amount');

        $totalAllBenefits = $components->filter(function ($component) {
            return $component->payrollComponent &&
                $component->payrollComponent->type === PayrollComponentType::BENEFIT;
        })->sum('amount');

        $deduction = $components->filter(function ($component) {
            return $component->payrollComponent &&
                $component->payrollComponent->type === PayrollComponentType::DEDUCTION;
        })->sum('amount');

        $grossSalaryThisMonth = $basicSalary + $allowanceTaxable + $additionalEarning + $taxableBenefit;

        $thrThisMonth = round($basicSalary + $additionalEarning);

        $runThrUser->update([
            'basic_salary' => $basicSalary,
            'gross_salary' => $grossSalaryThisMonth,
            'thr' => $thrThisMonth,
            'allowance' => $allowanceTaxable + $allowanceNonTaxable,
            'additional_earning' => $additionalEarning,
            'deduction' => $deduction,
            'benefit' => $totalAllBenefits,
            'payroll_info' => $runThrUser->user->payrollInfo,
        ]);
    }

    private function updateUserSalaryFields(RunThrUser $runThrUser): void
    {
        // Get all components for this user
        $components = $runThrUser->components()
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

        // Update RunThrUser with basic fields, totals will be calculated by refreshRunThrUser
        $runThrUser->update([
            'basic_salary' => $basicSalary,
            'allowance' => $allowance,
            'deduction' => $deduction,
            'benefit' => $benefit,
        ]);

        $this->refreshRunThrUser($runThrUser);
    }
}
