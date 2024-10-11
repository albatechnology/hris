<?php

namespace App\Exports;

use App\Models\Schedule;
use App\Models\Shift;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ImportScheduleShiftsExport implements FromView
{
    function __construct(public Schedule $schedule) {}

    public function view(): View
    {
        $this->schedule->load(['shifts' => fn($q) => $q->orderBy('order')]);
        $shifts = Shift::where('company_id', $this->schedule->company_id)->get();
        return view('api.imports.import-schedule-shifts', [
            'schedule' => $this->schedule,
            'shifts' => $shifts,
        ]);
    }
}
