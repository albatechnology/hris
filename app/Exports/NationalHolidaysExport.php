<?php

namespace App\Exports;

use App\Models\NationalHoliday;
use Maatwebsite\Excel\Concerns\FromCollection;

class NationalHolidaysExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return NationalHoliday::all();
    }
}
