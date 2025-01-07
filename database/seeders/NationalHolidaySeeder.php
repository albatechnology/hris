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
            ['name' => 'Tahun Baru Masehi', 'date' => '2024-01-01'],
            ['name' => 'Isra Miraj', 'date' => '2024-02-08'],
            ['name' => 'Tahun Baru Imlek', 'date' => '2024-02-10'],
            ['name' => 'Hari Suci Nyepi', 'date' => '2024-03-11'],
            ['name' => 'Wafatnya Isa Almasih', 'date' => '2024-03-29'],
            ['name' => 'Hari Raya Paskah', 'date' => '2024-03-31'],
            ['name' => 'Hari Raya Idul Fitfi 1445 H', 'date' => '2024-04-10'],
            ['name' => 'Hari Raya Idul Fitfi 1445 H', 'date' => '2024-04-11'],
            ['name' => 'Hari Buruh', 'date' => '2024-05-01'],
            ['name' => 'Kenaikan Isa Almasih', 'date' => '2024-05-09'],
            ['name' => 'Hari Raya Waisak', 'date' => '2024-05-23'],
            ['name' => 'Hari Lahir Pancasila', 'date' => '2024-06-01'],
            ['name' => 'Hari Raya Idul Adha 1445 H', 'date' => '2024-06-17'],
            ['name' => 'Tahun Baru Hijriah', 'date' => '2024-07-07'],
            ['name' => 'Hari Kemerdekaan RI', 'date' => '2024-08-17'],
            ['name' => 'Maulid Nabi Muhammad SAW', 'date' => '2024-09-16'],
            ['name' => 'Hari Raya Natal', 'date' => '2024-12-25'],
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
        // NationalHoliday::create(['name' => 'Tahun Baru Masehi', 'date' => '2024-01-01']);
        // NationalHoliday::create(['name' => 'Isra Miraj', 'date' => '2024-02-08']);
        // NationalHoliday::create(['name' => 'Tahun Baru Imlek', 'date' => '2024-02-10']);
        // NationalHoliday::create(['name' => 'Hari Suci Nyepi', 'date' => '2024-03-11']);
        // NationalHoliday::create(['name' => 'Wafatnya Isa Almasih', 'date' => '2024-03-29']);
        // NationalHoliday::create(['name' => 'Hari Raya Paskah', 'date' => '2024-03-31']);
        // NationalHoliday::create(['name' => 'Hari Raya Idul Fitfi 1445 H', 'date' => '2024-04-10']);
        // NationalHoliday::create(['name' => 'Hari Raya Idul Fitfi 1445 H', 'date' => '2024-04-11']);
        // NationalHoliday::create(['name' => 'Hari Buruh', 'date' => '2024-05-01']);
        // NationalHoliday::create(['name' => 'Kenaikan Isa Almasih', 'date' => '2024-05-09']);
        // NationalHoliday::create(['name' => 'Hari Raya Waisak', 'date' => '2024-05-23']);
        // NationalHoliday::create(['name' => 'Hari Lahir Pancasila', 'date' => '2024-06-01']);
        // NationalHoliday::create(['name' => 'Hari Raya Idul Adha 1445 H', 'date' => '2024-06-17']);
        // NationalHoliday::create(['name' => 'Tahun Baru Hijriah', 'date' => '2024-07-07']);
        // NationalHoliday::create(['name' => 'Hari Kemerdekaan RI', 'date' => '2024-08-17']);
        // NationalHoliday::create(['name' => 'Maulid Nabi Muhammad SAW', 'date' => '2024-09-16']);
        // NationalHoliday::create(['name' => 'Hari Raya Natal', 'date' => '2024-12-25']);
    }
}
