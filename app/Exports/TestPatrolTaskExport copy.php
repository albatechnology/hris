<?php

namespace App\Exports;

use App\Models\Patrol;
use App\Models\UserPatrolBatch;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class TestPatrolTaskExportCopy implements FromView
{
    use Exportable;

    public function __construct(private UserPatrolBatch $userPatrolBatch) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        return view('api.exports.patrol.test-export', [
            'userPatrolBatch' => $this->userPatrolBatch,
        ]);
    }
}
