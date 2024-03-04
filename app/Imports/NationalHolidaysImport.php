<?php

namespace App\Imports;

use App\Models\NationalHoliday;
use Maatwebsite\Excel\Concerns\ToModel;

class NationalHolidaysImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new NationalHoliday([
            'name' => $row['name'],
            'date' => $row['date'],
        ]);
    }
}
