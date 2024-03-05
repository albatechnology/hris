<?php

namespace App\Imports;

use App\Models\NationalHoliday;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class NationalHolidaysImport implements ToModel, WithHeadingRow
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
            'date' => date('Y-m-d', strtotime($row['date'])),
        ]);
    }
}
