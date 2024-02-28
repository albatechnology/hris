<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ScheduleShift;
use App\Models\Shift;
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
            // $schedule->shifts()->attach([...$shiftIds, 1, 1]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 2,
                'order' => 1
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 3,
                'order' => 2
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 4,
                'order' => 3
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 5,
                'order' => 4
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 6,
                'order' => 5
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 1,
                'order' => 6
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 1,
                'order' => 7
            ]);

            $schedule = $company->schedules()->create([
                'name' => 'Schedule 2 ' . $company->name,
                'effective_date' => date('Y-m-t'),
            ]);
            // $schedule->shifts()->attach([...$shiftIds, 1, 1]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 2,
                'order' => 1
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 3,
                'order' => 2
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 4,
                'order' => 3
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 5,
                'order' => 4
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 6,
                'order' => 5
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 1,
                'order' => 6
            ]);
            ScheduleShift::create([
                'schedule_id' => $schedule->id,
                'shift_id' => 1,
                'order' => 7
            ]);
        });
    }
}
