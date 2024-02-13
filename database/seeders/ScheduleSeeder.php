<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //global shift for dayoff
        Shift::create([
            'name' => 'dayoff',
            'is_dayoff' => true,
            'clock_in' => '00:00:00',
            'clock_out' => '00:00:00',
        ]);

        $dataShifts = [
            [
                'name' => 'Shift Senin',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ],
            [
                'name' => 'Shift Selasa',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ],
            [
                'name' => 'Shift Rabu',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ],
            [
                'name' => 'Shift Kamis',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ],
            [
                'name' => 'Shift Jumat',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ],
        ];

        Company::all()->each(function ($company) use ($dataShifts) {
            $shifts = $company->shifts()->createMany($dataShifts);
            $shiftIds = collect($shifts)->pluck('id');

            $schedule = $company->schedules()->create([
                'name' => 'Schedule 1 ' . $company->name,
                'effective_date' => date('Y-m-01'),
            ]);
            $schedule->shifts()->sync($shiftIds);

            $schedule = $company->schedules()->create([
                'name' => 'Schedule 2 ' . $company->name,
                'effective_date' => date('Y-m-t'),
            ]);
            $schedule->shifts()->sync($shiftIds);
        });
    }
}
