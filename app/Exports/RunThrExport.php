<?php

namespace App\Exports;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentType;
use App\Models\PayrollComponent;
use App\Models\RunThr;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RunThrExport implements FromView, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    use Exportable;

    public function __construct(public RunThr $runThr) {}

    public function view(): View
    {
        $payrollComponents = PayrollComponent::active()->where('company_id', $this->runThr->company_id)->get(['id', 'name', 'type', 'category','is_calculate_thr']);

        $allowances = $payrollComponents->where('type', PayrollComponentType::ALLOWANCE)->where('category', '!=', PayrollComponentCategory::BASIC_SALARY)->where('is_calculate_thr', true);
        $deductions = $payrollComponents->where('type', PayrollComponentType::DEDUCTION);
        $benefits = $payrollComponents->where('type', PayrollComponentType::BENEFIT)->whereNotIn('category', [PayrollComponentCategory::BPJS_KESEHATAN, PayrollComponentCategory::BPJS_KETENAGAKERJAAN]);

        $runThrUsers = $this->runThr->users;

        $cutOffStartDate = Carbon::parse($this->runThr->cut_off_start_date);
        $cutOffEndDate = Carbon::parse($this->runThr->cut_off_end_date);

        $runThrUsers = $this->runThr->users->groupBy(function ($item, $key) use ($cutOffStartDate, $cutOffEndDate) {
            return $item->user->resign_date && Carbon::parse($item->user->resign_date)->between($cutOffStartDate, $cutOffEndDate) ? 'resign' : (Carbon::parse($item->user->join_date)->between($cutOffStartDate, $cutOffEndDate) ? 'new' : 'active');
        });

        $activeUsers = $runThrUsers->get('active')?->sortBy('user.payrollInfo.bank.account_holder')->groupBy('user.payrollInfo.bank.id') ?? [];
        $resignUsers = $runThrUsers->get('resign')?->sortBy('user.payrollInfo.bank.account_holder')->groupBy('user.payrollInfo.bank.id') ?? [];
        $newUsers = $runThrUsers->get('new')?->sortBy('user.payrollInfo.bank.account_holder')->groupBy('user.payrollInfo.bank.id') ?? [];

        // $runThrUsersGroups = $runThrUsers->sortBy('user.payrollInfo.bank.account_holder')->groupBy('user.payrollInfo.bank.id');

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

        return view('api.exports.payroll.run-thr', [
            'runThr' => $this->runThr,
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
            'totalColumns' =>  23 +  $deductions->count() + $benefits->count() + $allowances->count(),
            // 'totalColumns' =>  21 +  $allowances->count() + $deductions->count() + $benefits->count()
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
            'AP' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
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
