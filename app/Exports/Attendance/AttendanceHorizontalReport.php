<?php

namespace App\Exports\Attendance;

use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AttendanceHorizontalReport implements FromView, ShouldAutoSize
{
    public function __construct(private CarbonPeriod $dateRange, private array $data) {}
    /**
     * @return array
     */
    // public function view(): View
    public function view(): View
    {
        return view('api.exports.attendance.report-attendance-horizontal', [
            'dateRange' => $this->dateRange,
            'data' => $this->data,
        ]);
    }
}
