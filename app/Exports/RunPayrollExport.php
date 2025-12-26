<?php

namespace App\Exports;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentType;
use App\Models\PayrollComponent;
use App\Models\RunPayroll;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RunPayrollExport implements FromView, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    use Exportable;

    public function __construct(public RunPayroll $runPayroll) {}

    public function view(): View
    {
        $payrollComponents = PayrollComponent::active()->where('company_id', $this->runPayroll->company_id)->get(['id', 'name', 'type', 'category']);

        $allowances = $payrollComponents->where('type', PayrollComponentType::ALLOWANCE)->where('category', '!=', PayrollComponentCategory::BASIC_SALARY);
        $deductions = $payrollComponents->where('type', PayrollComponentType::DEDUCTION);
        $benefits = $payrollComponents->where('type', PayrollComponentType::BENEFIT)->whereNotIn('category', [PayrollComponentCategory::BPJS_KESEHATAN, PayrollComponentCategory::BPJS_KETENAGAKERJAAN]);

        // $runPayrollUsers = $this->runPayroll->users;

        $payrollStartDate = Carbon::parse($this->runPayroll->payroll_start_date);
        $payrollEndDate = Carbon::parse($this->runPayroll->payroll_end_date);

        $runPayrollUsers = $this->runPayroll->users->groupBy(function ($item, $key) use ($payrollStartDate, $payrollEndDate) {
            return $item->user->resign_date && (Carbon::parse($item->user->resign_date)->between($payrollStartDate, $payrollEndDate)) ? 'resign' : (Carbon::parse($item->user->join_date)->between($payrollStartDate, $payrollEndDate) ? 'new' : 'active');
        });

        // $runPayrollUsers = $this->runPayroll->users->groupBy(fn($item, $key) => 'active');
        $activeUsers = $runPayrollUsers->get('active')?->sortBy('user.payrollInfo.bank.account_holder')->groupBy('user.payrollInfo.bank.id') ?? [];
        $resignUsers = $runPayrollUsers->get('resign')?->sortBy('user.payrollInfo.bank.account_holder')->groupBy('user.payrollInfo.bank.id') ?? [];
        $newUsers = $runPayrollUsers->get('new')?->sortBy('user.payrollInfo.bank.account_holder')->groupBy('user.payrollInfo.bank.id') ?? [];

        // $runPayrollUsersGroups = $runPayrollUsers->sortBy('user.payrollInfo.bank.account_holder')->groupBy('user.payrollInfo.bank.id');

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

        $viewName = 'api.exports.payroll.run-payroll';
        $totalColumns = 21;
        if (config('app.name') == 'LUMORA') {
            $viewName = 'api.exports.payroll.run-payroll-lumora';
            $totalColumns = 19;
        }

        return view($viewName, [
            'runPayroll' => $this->runPayroll,
            'activeUsers' => $activeUsers,
            'resignUsers' => $resignUsers,
            'newUsers' => $newUsers,
            'payrollComponentType' => PayrollComponentType::class,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'benefits' => $benefits,
            'totalAllowancesStorages' => $totalAllowancesStorages,
            'totalDeductionsStorages' => $totalDeductionsStorages,
            'totalBenefitsStorages' => $totalBenefitsStorages,
            'totalColumns' =>  $totalColumns +  $allowances->count() + $deductions->count() + $benefits->count()
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
            'N' => NumberFormat::FORMAT_TEXT,
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'Q' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'R' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'T' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'U' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'V' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'W' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'X' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'Y' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'Z' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AA' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AB' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AC' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AD' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AE' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AF' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AG' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AH' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AI' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AJ' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AK' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AL' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AM' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AN' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'AO' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->freezePane('A3');
            },
        ];
    }
}
