<?php

namespace Database\Seeders;

use App\Enums\WorkingPeriod;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $description = "<p>Notes :</p>
        <ol>
            <li>If a teacher teaches more than 108 hours per month, s(he) us entitled to overtime</li>
            <li>If a teacher teaches less than 60 hours per month, it means s(he) is less productive, therefore branch need more opening class or teaching assignment</li>
            <li>Student Advisor &amp; Teacher position doesn't have monimim teaching hours</li>
            <li>Student Advisor &amp; Teacher main responsibility is selling product/program/project</li>
            <li>Student Advisor &amp; Teacher adn Team Leader are able to teach if all teachers in the branch(es) aren't available to teach</li>
        </ol>";
        Company::all()->each(function ($company) use ($description) {
            $task = Task::create([
                'company_id' => $company->id,
                'name' => "Teaching",
                'min_working_hour' => 39,
                'working_period' => WorkingPeriod::MONTHLY,
                'description' => $description,
                'weekday_overtime_rate' => 50000,
                'weekend_overtime_rate' => 75000,
            ]);

            $taskHour = $task->hours()->create(
                [
                    'name' => 'Teacher',
                    'min_working_hour' => 60,
                    'max_working_hour' => 108,
                    'hours' => [
                        [
                            'name' => 'Monday - Friday Shift 1',
                            'clock_in' => '09:00',
                            'clock_out' => '17:00',
                        ],
                        [
                            'name' => 'Monday - Friday Shift 2',
                            'clock_in' => '12:00',
                            'clock_out' => '20:00',
                        ],
                        [
                            'name' => 'Saturday',
                            'clock_in' => '09:00',
                            'clock_out' => '14:00',
                        ]
                    ],
                ],
            );

            $task->users()->attach(User::where('company_id', $company->id)->get(['id'])->pluck('id'), ['task_hour_id' => $taskHour->id]);

            $taskHour = $task->hours()->create(
                [
                    'name' => 'Student Advisor & Teacher',
                    'min_working_hour' => 0,
                    'max_working_hour' => 80,
                    'hours' => [
                        [
                            'name' => 'Monday - Friday Shift 1',
                            'clock_in' => '09:00',
                            'clock_out' => '17:00',
                        ],
                        [
                            'name' => 'Monday - Friday Shift 2',
                            'clock_in' => '12:00',
                            'clock_out' => '20:00',
                        ],
                        [
                            'name' => 'Saturday',
                            'clock_in' => '09:00',
                            'clock_out' => '14:00',
                        ]
                    ],
                ]
            );

            $taskHour = $task->hours()->create(
                [
                    'name' => 'Team Leader',
                    'min_working_hour' => 40,
                    'max_working_hour' => 80,
                    'hours' => [
                        [
                            'name' => 'Monday - Friday Shift 1',
                            'clock_in' => '09:00',
                            'clock_out' => '17:00',
                        ],
                        [
                            'name' => 'Monday - Friday Shift 2',
                            'clock_in' => '12:00',
                            'clock_out' => '20:00',
                        ],
                        [
                            'name' => 'Saturday',
                            'clock_in' => '09:00',
                            'clock_out' => '14:00',
                        ]
                    ],
                ]
            );
        });
    }
}
