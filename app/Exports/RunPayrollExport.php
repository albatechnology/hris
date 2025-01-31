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

        return view('api.exports.payroll.run-payroll', [
            'runPayroll' => $this->runPayroll,
            'payrollComponentType' => PayrollComponentType::class,
            'allowances' => $payrollComponents->where('type', PayrollComponentType::ALLOWANCE)->where('category', '!=', PayrollComponentCategory::BASIC_SALARY),
            'deductions' => $payrollComponents->where('type', PayrollComponentType::DEDUCTION),
            'benefits' => $payrollComponents->where('type', PayrollComponentType::BENEFIT)->whereNotIn('category', [PayrollComponentCategory::BPJS_KESEHATAN, PayrollComponentCategory::BPJS_KETENAGAKERJAAN]),
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'I' => NumberFormat::FORMAT_GENERAL
        ];
    }
}
