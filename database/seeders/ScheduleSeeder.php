<?php

namespace Database\Seeders;

use App\Enums\ScheduleType;
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

            $schedules = [
                [
                    'name' => 'Schedule Attendance ' . $company->name,
                    'effective_date' => date('Y') . '-01-01` ',
                ],
                [
                    'name' => 'Schedule Patrol ' . $company->name,
                    'effective_date' => date('Y') . '-01-01` ',
                    'type' => ScheduleType::PATROL
                ]
            ];

            foreach ($schedules as $dataSchedule) {
                $schedule = $company->schedules()->create($dataSchedule);

                $order = 1;
                foreach ($shifts as $shift) {
                    ScheduleShift::create([
                        'schedule_id' => $schedule->id,
                        'shift_id' => $shift->id,
                        'order' => $order++
                    ]);
                }

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
            }
        });
    }
}
