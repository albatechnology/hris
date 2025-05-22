<?php

namespace App\Exports\Overtime;

use App\Enums\RateType;
use App\Http\Requests\Api\OvertimeRequest\ExportReportRequest;
use App\Models\Company;
use App\Models\Overtime;
use App\Models\OvertimeRequest;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ExportOvertimeRequest implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithEvents
{
    use Exportable;
    public Collection $overtimes;
    public Collection $companies;
    public string $startDate;
    public string $endDate;

    public function __construct(private ExportReportRequest $request)
    {
        $this->overtimes = Overtime::tenanted()
            ->with([
                'overtimeMultipliers',
                'overtimeRoundings',
            ])
            ->get();

        if (!empty($request->filter['company_ids'])) {
            $companyIds = explode(',', $request->filter['company_ids']);
        } else {
            $user = auth()->user();
            $companyIds = [$user->company_id ?? $user->companies()->orderBy('company_id')->first()->company_id];
        }

        $this->companies = Company::select('id', 'name')->whereIn('id', $companyIds)->get();
        if ($this->companies->count() != count($companyIds)) {
            throw new UnprocessableEntityHttpException('Invalid company filter');
        }

        $this->startDate = $request->filter['start_date'];
        $this->endDate = $request->filter['end_date'];
    }

    public function collection()
    {
        $branchId = $this->request->filter['branch_id'] ?? null;
        $clientId = $this->request->filter['client_id'] ?? null;
        $userIds = $this->request->filter['user_ids'] ?? null;

        // $users = User::tenanted()
        //     ->select('id', 'nik', 'name', 'branch_id', 'company_id', 'client_id')
        //     ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        //     ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
        //     ->when($clientId, fn($q) => $q->where('client_id', $clientId))
        //     ->when($userIds, fn($q) => $q->whereIn('id', explode(',', $userIds)))
        //     ->with([
        //         'company' => fn($q) => $q->select('id', 'name'),
        //         'branch' => fn($q) => $q->select('id', 'name'),
        //         'detail' => fn($q) => $q->select('user_id', 'employment_status'),
        //         'overtimeRequests' => fn($q) => $q
        //             ->whereDateBetween($this->request->filter['start_date'], $this->request->filter['end_date'])
        //             ->approved()
        //     ])->get();
        $overtimeRequests = OvertimeRequest::whereDateBetween($this->startDate, $this->endDate)
            ->approved()
            ->whereHas('user', fn($q) => $q->whereIn('company_id', $this->companies->pluck('id')))
            ->when($userIds, fn($q) => $q->whereIn('id', explode(',', $userIds)))
            ->when($branchId, fn($q) => $q->whereHas('user', fn($q) => $q->where('branch_id', $branchId)))
            ->when($clientId, fn($q) => $q->whereHas('user', fn($q) => $q->where('client_id', $clientId)))
            ->with(
                'user',
                fn($q) => $q->select('id', 'nik', 'name', 'branch_id', 'company_id')
                    ->with([
                        'detail' => fn($q) => $q->select('user_id', 'employment_status'),
                        'payrollInfo' => fn($q) => $q->select('user_id', 'basic_salary'),
                    ])
            )
            ->orderBy('user_id')
            ->orderBy('date')
            ->get();

        return $overtimeRequests;
    }

    public function map($overtimeRequest): array
    {
        $dataHeader = [
            $overtimeRequest->user->nik,
            $overtimeRequest->user->name,
            $overtimeRequest->user->branch->name,
            $overtimeRequest->user->company->name,
            $overtimeRequest->user->detail->employment_status?->value,
            $overtimeRequest->date,
            $overtimeRequest->note,
        ];

        $overtime = $this->overtimes->where('id', $overtimeRequest->overtime_id)->first();
        if (!$overtime) {
            return [
                ...$dataHeader,
                0,
                0,
                0,
                0,
            ];
        }

        $overtimeDate = Carbon::parse($overtimeRequest->date);

        // set overtime duration to minutes. 02:00:00 become 120
        $overtimeDuration = Carbon::parse($overtimeRequest->duration)->diffInMinutes(Carbon::parse('00:00:00'), true);

        // dump($overtime->toArray());
        // dump($overtimeDuration);
        if ($overtimeRounding = $overtime->overtimeRoundings->where('start_minute', '<=', $overtimeDuration)->where('end_minute', '>=', $overtimeDuration)->first()) {
            $overtimeDuration = $overtimeRounding->rounded;
        }

        // set overtime duration to hour. 120 become 2
        $overtimeDuration = round($overtimeDuration / 60);

        // dump($overtimeDuration);
        $overtimeMultipliers = collect([
            [
                'duration' => 1,
                'multiply' => 1,
            ]
        ]);
        // dump($overtimeMultipliers);
        if ($overtime->overtimeMultipliers->count()) {
            if ($overtimeDate->isWeekday()) {
                $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', true)->sortBy('start_hour');
            } else {
                $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', false)->sortBy('start_hour');
            }
            $overtimeMultipliers = OvertimeService::calculateOvertimeBreakdown($overtimeDuration, $overtimeMultipliers);
        }
        // dump($overtimeMultipliers);

        $overtimeAmountMultiply = 0;
        // if overtime paid per day. else paid per hour
        if (!is_null($overtime->compensation_rate_per_day) && $overtime->compensation_rate_per_day > 0) {
            $overtimeAmountMultiply = $overtime->compensation_rate_per_day;
            // $amount += $multiply * $overtimeAmountMultiply;
        } else {
            switch ($overtime->rate_type) {
                case RateType::AMOUNT:
                    $overtimeAmountMultiply = $overtime->rate_amount;

                    break;
                case RateType::BASIC_SALARY:
                    $basicSalary = $overtimeRequest->user->payrollInfo?->basic_salary ?? 0;
                    $overtimeAmountMultiply = $basicSalary / $overtime->rate_amount;
                    break;
                // case RateType::ALLOWANCES:
                //     $overtimeAmountMultiply = 0;

                //     foreach ($overtime->overtimeAllowances as $overtimeAllowance) {
                //         $overtimeAmountMultiply += $overtimeAllowance->payrollComponent?->amount > 0 ? ($overtimeAllowance->payrollComponent?->amount / $overtimeAllowance->amount) : 0;
                //     }

                //     break;
                // case RateType::FORMULA:
                // dump('OKEE');
                //     // $overtimeAmountMultiply = FormulaService::calculate(user: $user, model: $overtime, formulas: $overtime->formulas, startPeriod: $cutOffStartDate, endPeriod: $cutOffEndDate);


                //     $overtimeAmountMultiply = self::calculateFormula($user, $overtimeRequest, $overtime, $overtime->formulas, $startPeriod, $endPeriod);
                //     break;
                default:
                    $overtimeAmountMultiply = 0;

                    break;
            }
            // $amount += ($overtimeDuration * $multiply) * $overtimeAmountMultiply;
        }
        $overtimeRate = $overtimeAmountMultiply;
        // dump($overtimeAmountMultiply);

        $overtimeMultiplier = 0;
        $totalPayment = $overtimeAmountMultiply;
        if ($overtimeMultipliers->count()) {
            // $overtimeAmountMultiply = $overtimeMultipliers->sum(function ($om) use ($overtimeAmountMultiply) {
            //     return ($om['duration'] * $om['multiply']) * $overtimeAmountMultiply;
            // });

            $totalPayment = 0;
            foreach ($overtimeMultipliers as $om) {
                $totalPayment += ($om['duration'] * $om['multiply']) * $overtimeAmountMultiply;

                $overtimeMultiplier += ($om['duration'] * $om['multiply']);
            }
        }

        return [
            ...$dataHeader,
            $overtimeDuration,
            $overtimeMultiplier,
            $overtimeRate,
            $totalPayment,
        ];
    }

    public function headings(): array
    {
        return [
            [
                $this->companies->pluck('name')->implode(', '),
            ],
            [
                "Overtime Report " . date('d F Y', strtotime($this->startDate)) . ' - ' . date('d F Y', strtotime($this->endDate)),
            ],
            [
                'NIK',
                'Name',
                'Branch',
                'Company',
                'Employment Status',
                'Date',
                'Note',
                'Overtime Duration',
                'Overtime Multiplier',
                'Overtime Rate',
                'Total Payment',
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Merge dari kolom A1 sampai K1
                $sheet->mergeCells('A1:K1');
                $sheet->mergeCells('A2:K2');

                // Center horizontal dan vertical
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);


                // Bold seluruh baris ke-3 (A3 sampai K3)
                $sheet->getStyle('A3:K3')->getFont()->setBold(true);
                $sheet->getRowDimension(3)->setRowHeight(30);
            },
        ];
    }
}
