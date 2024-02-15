<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'name' => 'Live Attendance Flexible ' . $company->name,
                'is_flexible' => true
            ]);
            $liveAttendance = $company->liveAttendances()->create([
                'name' => 'Live Attendance ' . $company->name,
                'is_flexible' => false
            ]);

            $liveAttendance->locations()->createMany([
                [
                    'radius' => '100',
                    'lat' => '-6.2326902',
                    'lng' => '106.6645009',
                ],
                [
                    'radius' => '10',
                    'lat' => '-6.1979966',
                    'lng' => '106.7425406',
                ],
            ]);

            User::where('company_id', $company->id)->update(['live_attendance_id' => $liveAttendance->id]);
        });
    }
}
