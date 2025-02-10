<?php

namespace App\Exports;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentType;
use App\Models\PayrollComponent;
use App\Models\RunPayroll;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RunPayrollExport implements FromView, WithColumnFormatting
{
    use Exportable;

    public function __construct(public RunPayroll $runPayroll) {}

    public function view(): View
    {
        $payrollComponents = PayrollComponent::where('company_id', $this->runPayroll->company_id)->get(['id', 'name', 'type', 'category']);

        $allowances = $payrollComponents->where('type', PayrollComponentType::ALLOWANCE)->where('category', '!=', PayrollComponentCategory::BASIC_SALARY);
        $deductions = $payrollComponents->where('type', PayrollComponentType::DEDUCTION);
        $benefits = $payrollComponents->where('type', PayrollComponentType::BENEFIT)->whereNotIn('category', [PayrollComponentCategory::BPJS_KESEHATAN, PayrollComponentCategory::BPJS_KETENAGAKERJAAN]);

        $runPayrollUsersGroups = $this->runPayroll->users->sortBy('user.payrollInfo.bank.account_holder')->groupBy('user.payrollInfo.bank.id');

        $totalAllowancesStorages = $allowances->values()->map(fn($allowance) => [
            $allowance->id => 0
        ])->reduce(function ($carry, $item) {
            return $carry + $item; // Menggabungkan array dengan mempertahankan key
        }, []);

        $totalDeductionsStorages = $deductions->values()->map(fn($deduction) => [
            $deduction->id => 0
        ])->reduce(function ($carry, $item) {
            return $carry + $item; // Menggabungkan array dengan mempertahankan key
        }, []);

        $totalBenefitsStorages = $benefits->values()->map(fn($benefit) => [
            $benefit->id => 0
        ])->reduce(function ($carry, $item) {
            return $carry + $item; // Menggabungkan array dengan mempertahankan key
        }, []);

        return view('api.exports.payroll.run-payroll', [
            'runPayroll' => $this->runPayroll,
            'runPayrollUsersGroups' => $runPayrollUsersGroups,
            'payrollComponentType' => PayrollComponentType::class,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'benefits' => $benefits,
            'totalAllowancesStorages' => $totalAllowancesStorages,
            'totalDeductionsStorages' => $totalDeductionsStorages,
            'totalBenefitsStorages' => $totalBenefitsStorages,
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_GENERAL,
            'I' => NumberFormat::FORMAT_GENERAL,
            'K' => NumberFormat::FORMAT_GENERAL,
            'N' => NumberFormat::FORMAT_GENERAL,
        ];
    }
}
