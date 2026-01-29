<?php

namespace App\Exports\Payroll;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentType;
use App\Models\PayrollComponent;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class TemplateImportPayroll implements WithMultipleSheets
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

class WorkSheet implements FromArray, WithEvents
{
    private const COMPONENTS_PER_TYPE = 10;
    private const EMPTY_ROWS = 10;

    public function array(): array
    {
        $data = [];

        // Row 1: Main headers
        $row1 = ['ID / NIK Employee', 'Full Name Employee', 'Tax'];
        $row1 = array_merge($row1, array_fill(0, self::COMPONENTS_PER_TYPE, 'Allowance Components'));
        $row1 = array_merge($row1, array_fill(0, self::COMPONENTS_PER_TYPE, 'Deduction Components'));
        $row1 = array_merge($row1, array_fill(0, self::COMPONENTS_PER_TYPE, 'Benefit Components'));
        $data[] = $row1;

        // Row 2: Sub-headers for component columns
        $row2 = ['', '', '']; // Empty cells for ID/NIK, Name, and Tax columns
        for ($type = 0; $type < 3; $type++) {
            for ($i = 1; $i <= self::COMPONENTS_PER_TYPE; $i++) {
                $row2[] = "Component Name (do not delete it, just replace it)";
            }
        }
        $data[] = $row2;

        // Empty rows for user input
        $totalColumns = 3 + (3 * self::COMPONENTS_PER_TYPE); // ID/NIK + Name + Tax + components
        for ($i = 0; $i < self::EMPTY_ROWS; $i++) {
            $data[] = array_fill(0, $totalColumns, 0);
        }

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Merge A1:A2
                $event->sheet->mergeCells('A1:A2');
                // Merge B1:B2
                $event->sheet->mergeCells('B1:B2');
                // Merge C1:C2
                $event->sheet->mergeCells('C1:C2');
            },
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
