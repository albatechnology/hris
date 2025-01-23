<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class UserShiftsReport implements FromView
{
    public function __construct(private array $data) {}

    /**
     * @return array
     */
    // public function view(): View
    public function view(): View
    {
        return view('api.exports.user-shifts-report', [
            'data' => $this->data
        ]);
    }
}
