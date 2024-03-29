<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class LiveAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::all()->each(function ($company) {
            $company->liveAttendances()->create([
                'name' => 'Live Attendance Flexible '.$company->name,
                'is_flexible' => true,
            ]);
            $liveAttendance = $company->liveAttendances()->create([
                'name' => 'Live Attendance '.$company->name,
                'is_flexible' => false,
            ]);

            $liveAttendance->locations()->createMany([
                [
                    'name' => 'Location 1',
                    'radius' => '100',
                    'lat' => '-6.2275964',
                    'lng' => '106.6575175',
                ],
                [
                    'name' => 'Location 2',
                    'radius' => '10',
                    'lat' => '-6.2229137',
                    'lng' => '106.6549371',
                ],
            ]);

            User::where('company_id', $company->id)->update(['live_attendance_id' => $liveAttendance->id]);
        });
    }
}
