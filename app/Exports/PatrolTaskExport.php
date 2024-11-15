<?php

namespace App\Exports;

use App\Models\Patrol;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class PatrolTaskExport implements FromView
{
    use Exportable;

    public function __construct(public Patrol $patrol) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        return view('api.exports.patrol.export', [
            'patrol' => $this->patrol
        ]);
    }
}
