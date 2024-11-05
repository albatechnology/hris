<?php

namespace Database\Seeders;

use App\Enums\TimeoffPolicyType;
use App\Models\Company;
use App\Models\Timeoff;
use Illuminate\Database\Seeder;

class TimeoffPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::all()->each(function ($company) {
            if ($company->id == 1) {
                $company->timeoffPolicies()->createMany([
                    [
                        'name' => 'Day Off',
                        'code' => 'DO',
                        'type' => TimeoffPolicyType::DAY_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'External Assignment',
                        'code' => 'E',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Extra Off',
                        'code' => 'EO',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Free Leave',
                        'code' => 'FL',
                        'type' => TimeoffPolicyType::FREE_LEAVE,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Half Leave End',
                        'code' => 'HLE',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Half Leave Start',
                        'code' => 'HLS',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Annual Leave',
                        'code' => 'L',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Massive Leave',
                        'code' => 'MS',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Pregnancy Leave',
                        'code' => 'PL',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Permission',
                        'code' => 'PR',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Sick With Certificate',
                        'code' => 'SDC',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Sick Without Certificate',
                        'code' => 'SWC',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ],
                    [
                        'name' => 'Unpaid Leave',
                        'code' => 'UL',
                        'type' => TimeoffPolicyType::TIME_OFF,
                        'effective_date' => date('Y-m-d'),
                    ]
                ]);
            } else {
                $company->timeoffPolicies()->create([
                    'name' => 'timeoff policy ' . $company->name,
                    'type' => TimeoffPolicyType::TIME_OFF,
                    'effective_date' => date('Y-m-d'),
                ]);
            }
        });
    }
}
