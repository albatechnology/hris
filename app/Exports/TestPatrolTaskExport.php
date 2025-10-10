<?php

namespace App\Exports;

use App\Models\UserPatrolBatch;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class TestPatrolTaskExport implements FromView, WithEvents, ShouldAutoSize
{
    use Exportable;

    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        $userPatrolBatch = UserPatrolBatch::with('userPatrolTasks.media')->find(449);
        return view('api.exports.patrol.test-export', [
            'userPatrolBatch' => $userPatrolBatch,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getRowDimension(2)->setRowHeight(100); // baris ke-2
                $event->sheet->getDefaultRowDimension()->setRowHeight(80); // semua row default
            },
        ];
    }
}
