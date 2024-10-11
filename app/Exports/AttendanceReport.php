<?php

namespace App\Exports;

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
