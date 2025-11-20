<?php

namespace App\Exports\Overtime;

use App\Http\Requests\Api\OvertimeRequest\ExportReportRequest;
use App\Models\Company;
use App\Models\TaskHour;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Services\ScheduleService;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class NewExportTaskOvertimeRequest implements WithMultipleSheets
{
    use Exportable;
    public Collection $taskHours;
    public Collection $companies;
    public string $startDate;
    public string $endDate;

    public function __construct(private ExportReportRequest $request)
    {
        $this->taskHours = TaskHour::tenanted()
            ->with([
                'task',
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


    /**
     * Return array of sheet instances (one per user)
     */
    public function sheets(): array
    {
        $users = $this->getUsers();

        $sheets = [];
        foreach ($users as $user) {
            $sheets[] = new class($user, $this->taskHours, $this->companies, $this->startDate, $this->endDate) implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithTitle {
                use Exportable;

                private $user;
                private Collection $taskHours;
                private Collection $companies;
                private string $startDate;
                private string $endDate;

                public function __construct($user, Collection $taskHours, Collection $companies, string $startDate, string $endDate)
                {
                    $this->user = $user;
                    $this->taskHours = $taskHours;
                    $this->companies = $companies;
                    $this->startDate = $startDate;
                    $this->endDate = $endDate;
                }

                public function collection()
                {
                    $tasks = $this->user->tasks;
                    if ($tasks->count() <= 0 || $this->user->taskRequests->count() <= 0) return collect([]);

                    $userInfo = [
                        $this->user->nik,
                        $this->user->name,
                        $this->user->branch->name,
                        $this->user->company->name,
                        $this->user->detail->employment_status?->value,
                    ];

                    $rows = [];
                    $cumulativeDurationHours = 0;

                    // For each taskHour, include all taskRequests for that task_hour for the user
                    foreach ($this->taskHours as $taskHour) {
                        $task = $tasks->where('id', $taskHour->task_id)->first();
                        $overtimeRequests = $this->user->taskRequests->where('task_hour_id', $taskHour->id);

                        if (!$task || $overtimeRequests->count() <= 0) continue;

                        foreach ($overtimeRequests as $req) {
                            $overtimeDate = Carbon::parse($req->start_at);
                            $start = Carbon::parse($req->start_at);
                            $end = Carbon::parse($req->end_at);

                            $diffInSeconds = $end->diffInSeconds($start);
                            $diffInHours = $diffInSeconds / 3600;

                            $minWorkingHour = $taskHour->min_working_hour ?? 0;

                            // Sunday: always paid fully using sunday rate regardless of min_working_hour
                            if ($overtimeDate->dayOfWeek === 0) {
                                $billableHours = $diffInHours;
                                $hasReachedThreshold = true; // mark as paid
                                $duration = gmdate('H:i:s', $diffInSeconds);
                                $rate = $billableHours * $task->sunday_overtime_rate;

                                $cumulativeDurationHours += $diffInHours;

                                $rows[] = array_merge($userInfo, [
                                    $req->note,
                                    $req->start_at,
                                    $req->end_at,
                                    $req->taskHour?->name,
                                    $duration,
                                    $rate,
                                    $diffInHours,  // Hour
                                    $cumulativeDurationHours,  // Duration Aggregate
                                    $overtimeDate->dayOfWeek,
                                    1,
                                    $minWorkingHour,
                                ]);

                                continue;
                            }

                            // Saturday: need to split if overtime goes beyond user's shift end
                            if ($overtimeDate->dayOfWeek === 6) {
                                $schedule = ScheduleService::getTodaySchedule($this->user, $req->start_at, [], ['clock_in', 'clock_out']);
                                $shift = $schedule?->shift ?? null;

                                if ($shift) {
                                    $shiftStart = Carbon::parse(date('Y-m-d ' . $shift->clock_in, strtotime($req->start_at)));
                                    $shiftEnd = Carbon::parse(date('Y-m-d ' . $shift->clock_out, strtotime($req->start_at)));
                                    if (strtotime($shift->clock_out) < strtotime($shift->clock_in)) {
                                        // shift crosses midnight
                                        $shiftEnd = $shiftEnd->addDay();
                                    }
                                } else {
                                    $shiftStart = null;
                                    $shiftEnd = null;
                                }

                                // If no shift info, treat entire duration as saturday-paid
                                if (!$shiftEnd) {
                                    $billableHours = $diffInHours;
                                    $hasReachedThreshold = true;
                                    $duration = gmdate('H:i:s', $diffInSeconds);
                                    $rate = $billableHours * $task->saturday_overtime_rate;

                                    $cumulativeDurationHours += $diffInHours;

                                    $rows[] = array_merge($userInfo, [
                                        $req->note,
                                        $req->start_at,
                                        $req->end_at,
                                        $req->taskHour?->name,
                                        $duration,
                                        $rate,
                                        $diffInHours,
                                        $cumulativeDurationHours,
                                        $overtimeDate->dayOfWeek,
                                        1,
                                        $minWorkingHour,
                                    ]);

                                    continue;
                                }

                                // Three cases: fully before shiftEnd, fully after shiftEnd, overlaps
                                if ($end->lessThanOrEqualTo($shiftEnd)) {
                                    // fully within shift -> treat like weekday (subject to min threshold)
                                    // fallthrough to weekday logic below
                                } elseif ($start->greaterThanOrEqualTo($shiftEnd)) {
                                    // fully after shift -> saturday-paid fully
                                    $billableHours = $diffInHours;
                                    $hasReachedThreshold = true;
                                    $duration = gmdate('H:i:s', $diffInSeconds);
                                    $rate = $billableHours * $task->saturday_overtime_rate;

                                    $cumulativeDurationHours += $diffInHours;

                                    $rows[] = array_merge($userInfo, [
                                        $req->note,
                                        $req->start_at,
                                        $req->end_at,
                                        $req->taskHour?->name,
                                        $duration,
                                        $rate,
                                        $diffInHours,
                                        $cumulativeDurationHours,
                                        $overtimeDate->dayOfWeek,
                                        1,
                                        $minWorkingHour,
                                    ]);

                                    continue;
                                } else {
                                    // overlaps: split into two segments
                                    $mid = $shiftEnd;

                                    // segment A: start -> mid (within shift) -> weekday logic
                                    $endA = $mid;
                                    $startA = $start;
                                    $diffSecA = $endA->diffInSeconds($startA);
                                    $diffHourA = $diffSecA / 3600;

                                    // compute billable for segment A using min_working_hour and cumulative
                                    $billableA = 0;
                                    $hasReachedA = false;
                                    if ($cumulativeDurationHours < $minWorkingHour) {
                                        if ($cumulativeDurationHours + $diffHourA <= $minWorkingHour) {
                                            $billableA = 0;
                                        } else {
                                            $billableA = ($cumulativeDurationHours + $diffHourA) - $minWorkingHour;
                                            $hasReachedA = true;
                                        }
                                    } else {
                                        $billableA = $diffHourA;
                                        $hasReachedA = true;
                                    }

                                    $cumulativeDurationHours += $diffHourA;

                                    $durationA = gmdate('H:i:s', $diffSecA);
                                    $rateA = $billableA * $task->weekday_overtime_rate;

                                    $rows[] = array_merge($userInfo, [
                                        $req->note,
                                        $startA->toDateTimeString(),
                                        $endA->toDateTimeString(),
                                        $req->taskHour?->name,
                                        $durationA,
                                        $rateA,
                                        $diffHourA,
                                        $cumulativeDurationHours,
                                        $overtimeDate->dayOfWeek,
                                        $hasReachedA ? 1 : 0,
                                        $minWorkingHour,
                                    ]);

                                    // segment B: mid -> end (after shift) -> saturday-paid fully
                                    $startB = $mid;
                                    $endB = $end;
                                    $diffSecB = $endB->diffInSeconds($startB);
                                    $diffHourB = $diffSecB / 3600;

                                    $billableB = $diffHourB; // paid fully on saturday
                                    $cumulativeDurationHours += $diffHourB;
                                    $durationB = gmdate('H:i:s', $diffSecB);
                                    $rateB = $billableB * $task->saturday_overtime_rate;

                                    $rows[] = array_merge($userInfo, [
                                        $req->note,
                                        $startB->toDateTimeString(),
                                        $endB->toDateTimeString(),
                                        $req->taskHour?->name,
                                        $durationB,
                                        $rateB,
                                        $diffHourB,
                                        $cumulativeDurationHours,
                                        $overtimeDate->dayOfWeek,
                                        1,
                                        $minWorkingHour,
                                    ]);

                                    continue;
                                }
                            }

                            // Weekday (or saturday fully within shift) -> apply min_working_hour logic
                            $billableHours = $diffInHours;
                            $hasReachedThreshold = false;

                            if ($cumulativeDurationHours < $minWorkingHour) {
                                if ($cumulativeDurationHours + $diffInHours <= $minWorkingHour) {
                                    $billableHours = 0;
                                } else {
                                    $billableHours = ($cumulativeDurationHours + $diffInHours) - $minWorkingHour;
                                    $hasReachedThreshold = true;
                                }
                            } else {
                                $hasReachedThreshold = true;
                            }

                            $cumulativeDurationHours += $diffInHours;
                            $duration = gmdate('H:i:s', $diffInSeconds);

                            $rate = $billableHours * $task->weekday_overtime_rate;

                            $rows[] = array_merge($userInfo, [
                                $req->note,
                                $req->start_at,
                                $req->end_at,
                                $req->taskHour?->name,
                                $duration,
                                $rate,
                                $diffInHours,
                                $cumulativeDurationHours,
                                $overtimeDate->dayOfWeek,
                                $hasReachedThreshold ? 1 : 0,
                                $minWorkingHour,
                            ]);
                        }
                    }

                    return collect($rows);
                }

                public function headings(): array
                {
                    return [
                        [
                            $this->companies->pluck('name')->implode(', '),
                        ],
                        [
                            "Task Request Report " . date('d F Y', strtotime($this->startDate)) . ' - ' . date('d F Y', strtotime($this->endDate)),
                        ],
                        [
                            'NIK',
                            'Name',
                            'Branch',
                            'Company',
                            'Employment Status',
                            'Note',
                            'Start At',
                            'End At',
                            'Task Hour Name',
                            'Duration',
                            'Total Payment',
                            'Hour',
                            'Duration Aggregate',
                        ]
                    ];
                }

                public function registerEvents(): array
                {
                    return [
                        AfterSheet::class => function (AfterSheet $event) {
                            $sheet = $event->sheet->getDelegate();
                            // Merge based on 13 columns A..M
                            $sheet->mergeCells('A1:M1');
                            $sheet->mergeCells('A2:M2');

                            // Center horizontal dan vertical
                            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle('A1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle('A2')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                            // Bold seluruh baris ke-3 (A3 sampai M3)
                            $sheet->getStyle('A3:M3')->getFont()->setBold(true);
                            $sheet->getRowDimension(3)->setRowHeight(30);
                            $sheet->getRowDimension(2)->setRowHeight(20);
                            $sheet->getRowDimension(1)->setRowHeight(20);

                            // Color rows and add Duration Aggregate formula
                            // Column N = dayOfWeek, Column O = hasReachedThreshold flag
                            // Saturday = 6 (teal), Sunday = 0 (blue), Others = pink if threshold reached
                            $highestRow = $sheet->getHighestRow();
                            for ($row = 4; $row <= $highestRow; $row++) {
                                $dayOfWeek = $sheet->getCell('N' . $row)->getValue();
                                $hasReachedThreshold = $sheet->getCell('O' . $row)->getValue();

                                // Add formula for Duration Aggregate (M column)
                                // Formula: Hour (L) + Previous Duration Aggregate (M-1)
                                if ($row == 4) {
                                    // First row: just the Hour value
                                    $sheet->setCellValue('M' . $row, '=L' . $row);
                                } else {
                                    // Subsequent rows: Hour + Previous Duration Aggregate
                                    $sheet->setCellValue('M' . $row, '=L' . $row . '+M' . ($row - 1));
                                }

                                // Apply row coloring based on priority:
                                // 1. Saturday (6) - Teal
                                // 2. Sunday (0) - Yellow
                                // 3. Other days with threshold reached - Pink
                                if ($dayOfWeek == 6) {
                                    // Sabtu - Teal color
                                    $sheet->getStyle('A' . $row . ':M' . $row)->getFill()
                                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                        ->getStartColor()->setARGB('FF008B8B'); // Teal
                                } elseif ($dayOfWeek == 0) {
                                    // Minggu - Blue color
                                    $sheet->getStyle('A' . $row . ':M' . $row)->getFill()
                                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                        ->getStartColor()->setARGB('FF0000FF'); // Blue
                                } elseif ($hasReachedThreshold == 1) {
                                    // Weekday with threshold reached - Pink color
                                    $sheet->getStyle('A' . $row . ':M' . $row)->getFill()
                                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                        ->getStartColor()->setARGB('FFFF69B4'); // Hot Pink
                                }
                            }

                            // Hide columns N (day of week), O (threshold flag) and P (min_working_hour reference)
                            $sheet->getColumnDimension('N')->setVisible(false);
                            $sheet->getColumnDimension('O')->setVisible(false);
                            $sheet->getColumnDimension('P')->setVisible(false);
                        },
                    ];
                }

                // Give each sheet a title limited to 31 characters
                public function title(): string
                {
                    $name = $this->user->name ?? 'User';
                    return mb_substr($name, 0, 31);
                }
            };
        }

        return $sheets;
    }

    /**
     * Fetch users to be exported (kept as separate method for clarity)
     */
    protected function getUsers()
    {
        $branchId = $this->request->filter['branch_id'] ?? null;
        $userIds = $this->request->filter['user_ids'] ?? null;

        $where = fn($q) => $q->whereDateBetween($this->startDate, $this->endDate)->approved();

        $users = User::select('id', 'nik', 'name', 'branch_id', 'company_id')
            ->whereIn('company_id', $this->companies->pluck('id'))
            ->when($userIds, fn($q) => $q->whereIn('id', explode(',', $userIds)))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->withWhereHas('tasks', fn($q) => $q->select('id', 'weekday_overtime_rate', 'saturday_overtime_rate', 'sunday_overtime_rate'))
            ->withWhereHas('taskRequests', fn($q) => $q->where($where)->orderBy('start_at')->with('taskHour'))
            ->with([
                'detail' => fn($q) => $q->select('user_id', 'employment_status'),
            ])
            ->get();

        return $users;
    }
}
