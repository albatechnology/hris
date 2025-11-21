<?php

namespace App\Exports\Overtime;

use App\Http\Requests\Api\OvertimeRequest\ExportReportRequest;
use App\Models\Company;
use App\Models\TaskHour;
use App\Models\User;
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

class ExportTaskOvertimeRequest implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithEvents
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

    public function collection()
    {
        $branchId = $this->request->filter['branch_id'] ?? null;
        $userIds = $this->request->filter['user_ids'] ?? null;

        $where = fn($q) => $q->whereDateBetween($this->startDate, $this->endDate)->approved();

        $users = User::select('id', 'nik', 'name', 'branch_id', 'company_id')
            ->whereIn('company_id', $this->companies->pluck('id'))
            ->when($userIds, fn($q) => $q->whereIn('id', explode(',', $userIds)))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('taskRequests', fn($q) => $q->where($where))
            ->with([
                'tasks' => fn($q) => $q->select('id', 'min_working_hour', 'working_period', 'weekday_overtime_rate', 'weekend_overtime_rate')->withPivot('task_hour_id'),
                'taskRequests' => fn($q) => $q->where($where)->orderBy('start_at')->with('taskHour'),
                'detail' => fn($q) => $q->select('user_id', 'employment_status'),
                'payrollInfo' => fn($q) => $q->select('user_id', 'basic_salary'),
            ])
            ->get();

        return $users;
    }


    public function map($user): array
    {
        $tasks = $user->tasks;

        if ($tasks->count() <= 0 || $user->taskRequests->count() <= 0) return [];

        $userInfo = [
            $user->nik,
            $user->name,
            $user->branch->name,
            $user->company->name,
            $user->detail->employment_status?->value,
        ];
        $datas = [];

        foreach ($this->taskHours as $taskHour) {
            $task = $tasks->where('id', $taskHour->task_id)->first();
            $overtimeRequests = $user->taskRequests->where('task_hour_id', $taskHour->id);

            if (!$task || $overtimeRequests->count() <= 0) continue;

            $totalDurationInHours = 0;
            $lastTaskRequest = null;
            foreach ($user->taskRequests as $taskRequest) {
                $start = Carbon::parse($taskRequest->start_at);
                $end = Carbon::parse($taskRequest->end_at);
                $totalDurationInHours += $end->diffInMinutes($start) / 60; // Hitung durasi dalam jam

                if ($totalDurationInHours > $taskHour->max_working_hour) {
                    $lastTaskRequest = $taskRequest;
                    break;
                }
            }

            if ($totalDurationInHours <= $taskHour->max_working_hour) continue;

            $newOvertimeRequests = $user->taskRequests->skipUntil(fn($d) => $d->id === $lastTaskRequest->id);
            foreach ($newOvertimeRequests as $newOvertimeRequest) {
                $overtimeDate = Carbon::parse($newOvertimeRequest->start_at);
                $start = Carbon::parse($newOvertimeRequest->start_at);
                $end = Carbon::parse($newOvertimeRequest->end_at);

                $diffInSeconds = $end->diffInSeconds($start);
                $diffInHours = $diffInSeconds / 3600;

                $duration = gmdate('H:i:s', $diffInSeconds);

                if ($overtimeDate->isWeekday()) {
                    $rate = $diffInHours * $task->weekday_overtime_rate;
                } else {
                    $rate = $diffInHours * $task->weekend_overtime_rate;
                }

                $datas[] = [
                    ...$userInfo,
                    $newOvertimeRequest->note,
                    $newOvertimeRequest->start_at,
                    $newOvertimeRequest->end_at,
                    $newOvertimeRequest->taskHour?->name,
                    $duration,
                    $rate,
                ];
            }
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
