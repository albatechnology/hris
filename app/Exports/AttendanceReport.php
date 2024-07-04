<?php

namespace App\Exports;

use App\Http\Requests\Api\Attendance\ExportReportRequest;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\NationalHoliday;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ScheduleService;
use App\Services\TaskService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class AttendanceReport implements FromView
{
    public function __construct(private array $data)
    {
    }
    /**
     * @return array
     */
    // public function view(): View
    public function view(): View
    {
        return view('api.exports.report-attendance', [
            'data' => $this->data
        ]);
    }
}
