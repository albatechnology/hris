<?php

namespace App\Exports;

use App\Models\Event;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class NationalHolidaysExport implements FromCollection, WithHeadings
{
    const HEADINGS = [
        'name',
        'date',
    ];

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Event::tenanted()->whereNationalHoliday()->get();
        // return NationalHoliday::all();
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    public static function getSample(): Collection
    {
        return collect([
            self::HEADINGS,
            [
                sprintf('Tahun Baru %s Masehi', date('Y')),
                date('Y') . '-01-01'
            ],
            [
                'Hari Buruh Internasional',
                date('Y') . '-05-01'
            ],
            [
                'Hari Lahir Pancasila',
                date('Y') . '-06-01'
            ],
            [
                'Hari Kemerdekaan Republik Indonesia',
                date('Y') . '-08-17'
            ],
        ]);
    }
}
