<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            GroupSeeder::class,
            // PositionSeeder::class,
            PermissionSeeder::class,
            ScheduleSeeder::class,
            UserSeeder::class,
            // LiveAttendanceSeeder::class,
            TimeoffPolicySeeder::class,
            CustomFieldSeeder::class,
            NationalHolidaySeeder::class,
            TaskSeeder::class,
            // PayrollComponentSeeder::class,
        ]);
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
