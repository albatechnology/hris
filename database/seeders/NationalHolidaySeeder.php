<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Models\Company;
use App\Models\Event;
use App\Models\NationalHoliday;
use Illuminate\Database\Seeder;

class NationalHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dates = [
            ['name' => 'Tahun Baru Masehi', 'date' => '2025-01-01'],
            ['name' => 'Isra Mikraj Nabi Muhammad SAW', 'date' => '2025-01-27'],
            ['name' => 'Tahun Baru Imlek 2576 Kongzili', 'date' => '2025-01-29'],
            ['name' => 'Hari Suci Nyepi (Tahun Baru Saka 1947)', 'date' => '2025-03-29'],
            ['name' => 'Hari Raya Idulfitri 1446 Hijriah', 'date' => '2025-03-31'],
            ['name' => 'Hari Raya Idulfitri 1446 Hijriah', 'date' => '2025-04-01'],
            ['name' => 'Wafat Yesus Kristus', 'date' => '2025-04-18'],
            ['name' => 'Hari Paskah', 'date' => '2025-04-20'],
            ['name' => 'Hari Buruh Internasional', 'date' => '2025-05-01'],
            ['name' => 'Hari Raya Waisak 2569 BE', 'date' => '2025-05-12'],
            ['name' => 'Kenaikan Yesus Kristus', 'date' => '2025-05-29'],
            ['name' => 'Hari Lahir Pancasila', 'date' => '2025-06-01'],
            ['name' => 'Hari Raya Idul Adha 1446 Hijriah', 'date' => '2025-06-06'],
            ['name' => 'Tahun Baru Islam 1447 Hijriah', 'date' => '2025-06-27'],
            ['name' => 'Hari Kemerdekaan Republik Indonesia', 'date' => '2025-08-17'],
            ['name' => 'Maulid Nabi Muhammad SAW', 'date' => '2025-09-05'],
            ['name' => 'Hari Raya Natal', 'date' => '2025-12-25'],
        ];

        Company::all()->each(function (Company $company) use ($dates) {
            collect($dates)->each(function ($date) use ($company) {
                NationalHoliday::create($date);

                $date['company_id'] = $company->id;
                $date['start_at'] = $date['date'];
                $date['type'] = EventType::NATIONAL_HOLIDAY->value;
                unset($date['date']);
                Event::create($date);
            });
        });

    }
}
