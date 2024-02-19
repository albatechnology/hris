<?php

namespace Database\Seeders;

use App\Enums\TimeoffPolicyType;
use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TimeoffPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Company::all()->each(function ($company) {
            $company->timeoffPolicies()->create([
                'name' => 'timeoff policy ' . $company->name,
                'type' => TimeoffPolicyType::TIME_OFF,
                'effective_date' => date('Y-m-d'),
            ]);
        });
    }
}
