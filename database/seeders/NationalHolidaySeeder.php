<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Models\Company;
use App\Models\Event;
use App\Models\NationalHoliday;
use App\Services\EventService;
use Illuminate\Database\Seeder;

class NationalHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dates = EventService::getCalendarDate();

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
