<?php

namespace App\Exports\Overtime;

use App\Enums\PayrollComponentCategory;
use App\Enums\RateType;
use App\Helpers\AttendanceHelper;
use App\Http\Requests\Api\OvertimeRequest\ExportReportRequest;
use App\Http\Services\Payroll\RunPayrollService;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Event;
use App\Models\Overtime;
use App\Models\PayrollComponent;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\OvertimeService;
use App\Services\ScheduleService;
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
    public Collection $payrollSettings;
    public Collection $basicSalaryPayrollComponents;
    public Collection $overtimes;
    public Collection $companies;
    public Collection $nationalHolidays;
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

        $this->nationalHolidays = Event::select('id', 'company_id', 'start_at', 'end_at')
            ->whereNationalHoliday()
            ->whereIn('company_id', $companyIds)
            ->whereDateBetween($this->startDate, $this->endDate)
            ->get();

        $this->payrollSettings = PayrollSetting::whereIn('company_id', $companyIds)->get();
        $this->basicSalaryPayrollComponents = PayrollComponent::where('category', PayrollComponentCategory::BASIC_SALARY)->get();
    }

    public function collection()
    {
        $branchId = $this->request->filter['branch_id'] ?? null;
        $userIds = $this->request->filter['user_ids'] ?? null;
        $overtimeIdsRaw = $this->request->filter['overtime_ids'] ?? null;
        $overtimeIds = $overtimeIdsRaw
            ? array_values(array_filter(array_map(fn($v) => (int) trim($v), explode(',', $overtimeIdsRaw)), fn($v) => $v > 0))
            : null;
        // $overtimeRequests = OvertimeRequest::whereDateBetween($this->startDate, $this->endDate)
        //     ->approved()
        //     ->whereHas('user', fn($q) => $q->whereIn('company_id', $this->companies->pluck('id')))
        //     ->when($userIds, fn($q) => $q->whereIn('user_id', explode(',', $userIds)))
        //     ->when($branchId, fn($q) => $q->whereHas('user', fn($q) => $q->where('branch_id', $branchId)))
        //     ->with([
        //         'overtime' => fn($q) => $q->select('id', 'name', 'rate_type', 'rate_amount', 'compensation_rate_per_day'),
        //         'user' => fn($q) => $q->select('id', 'nik', 'name', 'branch_id', 'company_id')
        //             ->with([
        //                 'detail' => fn($q) => $q->select('user_id', 'employment_status'),
        //                 'payrollInfo' => fn($q) => $q->select('user_id', 'basic_salary'),
        //             ]),
        //     ])
        //     ->orderBy('user_id')
        //     ->orderBy('date')
        //     ->get();

        $where = fn($q) => $q->whereDateBetween($this->startDate, $this->endDate)->approved();

        $users = User::select('id', 'nik', 'name', 'branch_id', 'company_id')
            ->whereIn('company_id', $this->companies->pluck('id'))
            ->when($userIds, fn($q) => $q->whereIn('id', explode(',', $userIds)))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            // ->whereHas('overtimeRequests', fn($q) => $q->where($where))
            ->whereHas('overtimeRequests', function ($q) use ($where, $overtimeIds) {
                $q->where($where)
                    ->when($overtimeIds, fn($qq) => $qq->whereIn('overtime_id', $overtimeIds));
            })
            // ->with([
            //     'overtimeRequests' => fn($q) => $q->where($where)->with('overtime', fn($q) => $q->select('id', 'name', 'rate_type', 'rate_amount', 'compensation_rate_per_day')),
            //     'detail' => fn($q) => $q->select('user_id', 'employment_status'),
            //     'payrollInfo' => fn($q) => $q->select('user_id', 'basic_salary'),
            // ])
            ->with([
                // Eager load overtimeRequests terfilter tanggal + overtime_ids
                'overtimeRequests' => function ($q) use ($where, $overtimeIds) {
                    $q->where($where)
                        ->when($overtimeIds, fn($qq) => $qq->whereIn('overtime_id', $overtimeIds))
                        ->with('overtime:id,name,rate_type,rate_amount,compensation_rate_per_day');
                },
                'detail' => fn($q) => $q->select('user_id', 'employment_status'),
                'payrollInfo' => fn($q) => $q->select('user_id', 'basic_salary', 'is_ignore_alpa'),
                'updatePayrollComponentDetails' => fn($q) => $q->whereHas('updatePayrollComponent', fn($q) => $q->whereActive($this->startDate, $this->endDate))
                    ->whereHas('payrollComponent', fn($q) => $q->where('category', PayrollComponentCategory::BASIC_SALARY))
                    ->with('payrollComponent')
                    ->with('updatePayrollComponent')
                    ->orderByDesc('id'),
                // optional: kurangi N+1 di map()
                'branch:id,name',
                'company:id,name',
            ])
            ->get();

        return $users;
    }


    public function map($user): array
    {
        $userInfo = [
            $user->nik,
            $user->name,
            $user->branch->name,
            $user->company->name,
            $user->detail->employment_status?->value,
        ];

        if ($user->overtimeRequests->count() <= 0) return [];

        $datas = [];
        $attendances = Attendance::select('id', 'date')->where('user_id', $user->id)->whereIn('date', $user->overtimeRequests->pluck('date'))->whereHas('details', fn($q) => $q->approved())->with([
            'clockIn' => fn($q) => $q->select('attendance_id', 'time'),
            'clockOut' => fn($q) => $q->select('attendance_id', 'time'),
        ])->get();


        $payrollSetting = $this->payrollSettings->where('company_id', $user->company_id)->first();
        $basicSalary = $user->payrollInfo?->basic_salary ?? 0;

        if (config('app.name') == 'SUNSHINE') {
        } else {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);
            $dataTotalAttendance = AttendanceHelper::getTotalAttendanceForPayroll($payrollSetting, $user, $startDate, $endDate);
            if ($user->updatePayrollComponentDetails->count()) {
                $updatePayrollComponentDetail = $user->updatePayrollComponentDetails[0];
                $startEffectiveDate = Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->effective_date);

                // end_date / endEffectiveDate can be null
                $endEffectiveDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ? Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->end_date) : null;

                $basicSalary = app(RunPayrollService::class)->newProrate($basicSalary, $updatePayrollComponentDetail->new_amount, $dataTotalAttendance, $startDate, $endDate, $startEffectiveDate, $endEffectiveDate);
            } else {
                $basicSalaryComponent = $this->basicSalaryPayrollComponents->where('company_id', $user->company_id)->first();
                if ($basicSalaryComponent?->is_prorate) {
                    $basicSalary = app(RunPayrollService::class)->newProrate(0, $basicSalary, $dataTotalAttendance, $startDate, $endDate, $startDate, $endDate);
                }
            }
        }

        foreach ($user->overtimeRequests as $overtimeRequest) {
            $attendance = $attendances->where('date', $overtimeRequest->date)->first();
            $dataHeader = [
                ...$userInfo,
                $overtimeRequest->date,
                $overtimeRequest->note,
                $attendance?->clockIn?->time,
                $attendance?->clockOut?->time,
                $overtimeRequest->is_after_shift ? 'After Shift' : 'Before Shift',
                $overtimeRequest->overtime->name,
                $overtimeRequest->real_duration,
            ];

            $overtime = $this->overtimes->where('id', $overtimeRequest->overtime_id)->first();
            if (!$overtime) {
                $datas[] = [
                    ...$dataHeader,
                    0,
                    0,
                    0,
                    0,
                    0,
                ];
                continue;
            }

            $overtimeMultiplier = 1;

            if (config('app.name') == 'SUNSHINE') {
                // from OvertimeService::calculateOb
                if (strtolower($overtimeRequest->overtime->name) == 'ob') {
                    // return OvertimeService::calculateOb($overtimeRequest->user, collect($overtimeRequest));
                    $user = $overtimeRequest->user;
                    $umk = $user->branch?->umk ?? 0;
                    $basicSalary = $user->payrollInfo?->basic_salary > $umk ? $user->payrollInfo?->basic_salary : $umk;

                    $durationInHours = OvertimeService::calculateOvertimeDuration($overtimeRequest->real_duration);
                    $durationInHours = ($durationInHours > 9 ? 9 : $durationInHours);
                    $overtimeRate = ($basicSalary / 160);
                    $totalPayment = $overtimeRate * $durationInHours;

                    $datas[] = [
                        ...$dataHeader,
                        $durationInHours,
                        $overtimeMultiplier,
                        $overtimeRate,
                        $totalPayment,
                    ];
                    continue;
                }

                // from OvertimeService::calculateObSunEnglish
                if (strtolower($overtimeRequest->overtime->name) == 'ob_sun_english') {
                    // return OvertimeService::calculateObSunEnglish($overtimeRequest->user, $overtime, collect($overtimeRequest));
                    $durationInHours = OvertimeService::calculateOvertimeDuration($overtimeRequest->real_duration);
                    $durationInHours = ($durationInHours > 9 ? 9 : $durationInHours);

                    $overtimeRate = $overtime->rate_type->is(RateType::AMOUNT) && !is_null($overtime->rate_amount) ? floatval($overtime->rate_amount) : 17500;

                    $totalPayment = $overtimeRate * $durationInHours;

                    $datas[] = [
                        ...$dataHeader,
                        $durationInHours,
                        $overtimeMultiplier,
                        $overtimeRate,
                        $totalPayment,
                    ];
                    continue;
                }
            }
            $overtimeDate = Carbon::parse($overtimeRequest->date);

            // set overtime duration to minutes. 02:00:00 become 120
            $overtimeDuration = Carbon::parse($overtimeRequest->real_duration)->diffInMinutes(Carbon::parse('00:00:00'), true);

            if ($overtimeRounding = $overtime->overtimeRoundings->where('start_minute', '<=', $overtimeDuration)->where('end_minute', '>=', $overtimeDuration)->first()) {
                $overtimeDuration = $overtimeRounding->rounded;
            }

            // set overtime duration to hour. 120 become 2
            // $durationInHours = round($overtimeDuration / 60);
            $durationInHours = floor($overtimeDuration / 60);
            $durationInHours += OvertimeService::roundingOvertimeMinutes($overtimeDuration % 60);

            $overtimeMultipliers = collect([
                [
                    'duration' => 1,
                    'multiply' => 1,
                ]
            ]);

            if ($overtime->overtimeMultipliers->count()) {
                if (config('app.name') == 'LUMORA') {
                    $inNationalHoliday = $this->nationalHolidays->where('company_id', $overtimeRequest->user->company_id)->contains(function ($nh) use ($overtimeDate) {
                        $start = Carbon::parse($nh->start_at)->startOfDay();
                        $end = Carbon::parse($nh->end_at)->endOfDay();
                        return $overtimeDate->between($start, $end);
                    });

                    if (!$inNationalHoliday) {
                        $user = User::select('id')->where('id', $overtimeRequest->user_id)->firstOrFail();
                        $schedule = ScheduleService::getTodaySchedule(user: $user, datetime: $overtimeRequest->date, scheduleColumn: ['id'], shiftColumn: ['id', 'is_dayoff']);
                    }

                    if ($inNationalHoliday) {
                        $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', false)->sortBy('start_hour');
                    } elseif (!$inNationalHoliday && $schedule?->shift?->is_dayoff) {
                        $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', false)->sortBy('start_hour');
                    } else {
                        $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', true)->sortBy('start_hour');
                    }
                } else {
                    if ($overtimeDate->isWeekday()) {
                        $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', true)->sortBy('start_hour');
                    } else {
                        $overtimeMultipliers = $overtime->overtimeMultipliers->where('is_weekday', false)->sortBy('start_hour');
                    }
                }

                $overtimeMultipliers = OvertimeService::calculateOvertimeBreakdown($durationInHours, $overtimeMultipliers);
            }

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
                        $overtimeAmountMultiply = $basicSalary / $overtime->rate_amount;
                        break;
                    // case RateType::ALLOWANCES:
                    //     $overtimeAmountMultiply = 0;

                    //     foreach ($overtime->overtimeAllowances as $overtimeAllowance) {
                    //         $overtimeAmountMultiply += $overtimeAllowance->payrollComponent?->amount > 0 ? ($overtimeAllowance->payrollComponent?->amount / $overtimeAllowance->amount) : 0;
                    //     }

                    //     break;
                    case RateType::FORMULA:
                        // $overtimeAmountMultiply = FormulaService::calculate(user: $user, model: $overtime, formulas: $overtime->formulas, startPeriod: $cutOffStartDate, endPeriod: $cutOffEndDate);

                        $overtimeAmountMultiply = OvertimeService::calculateFormula($user, $overtimeRequest, $overtime, $overtime->formulas, $this->startDate, $this->endDate);
                        break;
                    default:
                        $overtimeAmountMultiply = 0;

                        break;
                }
                // $amount += ($durationInHours * $multiply) * $overtimeAmountMultiply;
            }
            $overtimeRate = $overtimeAmountMultiply;

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

            $datas[] = [
                ...$dataHeader,
                $durationInHours,
                $overtimeMultiplier,
                $overtimeRate,
                $totalPayment,
            ];
        }

        return $datas;
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
                'Clock In',
                'Clock Out',
                'Overtime Time',
                'Overtime Category',
                'Real Duration',
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
                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');

                // Center horizontal dan vertical
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // Bold seluruh baris ke-3 (A3 sampai L3)
                $sheet->getStyle('A3:L3')->getFont()->setBold(true);
                $sheet->getRowDimension(3)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(1)->setRowHeight(20);
            },
        ];
    }
}
