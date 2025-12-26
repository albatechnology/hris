<?php

namespace App\Exports\UpdatePayrollComponent;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentType;
use App\Models\PayrollComponent;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class TemplateImportDetails implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private int $companyId) {}

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new WorkSheet(),
            new ListOfComponents($this->companyId)
        ];
    }
}

class WorkSheet implements WithHeadings
{
    public function headings(): array
    {
        return [
            'ID / NIK Employee',
            'Full Name Employee',
            'Component Name',
            'Old Amount',
            'New Amount',
        ];
    }
}

class ListOfComponents implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private int $companyId) {}

    public function query()
    {
        return PayrollComponent::active()->select('id', 'name', 'type')->whereCompany($this->companyId)->where('type', '!=', PayrollComponentType::BENEFIT)->whereNotIn('category', [
            PayrollComponentCategory::COMPANY_BPJS_KESEHATAN,
            PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN,
            PayrollComponentCategory::COMPANY_JKK,
            PayrollComponentCategory::COMPANY_JKM,
            PayrollComponentCategory::COMPANY_JHT,
            PayrollComponentCategory::EMPLOYEE_JHT,
            PayrollComponentCategory::COMPANY_JP,
            PayrollComponentCategory::EMPLOYEE_JP,
            PayrollComponentCategory::BPJS_KESEHATAN_FAMILY,
        ]);
    }

    public function headings(): array
    {
        return [
            'Component ID',
            'Component Name',
            'Type',
        ];
    }

    public function map($payrollComponent): array
    {
        return [
            $payrollComponent->id,
            $payrollComponent->name,
            $payrollComponent->type->value,
        ];
    }

    public function title(): string
    {
        return "list of components";
    }
}
