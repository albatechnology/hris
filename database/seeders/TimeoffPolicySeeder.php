<?php

namespace Database\Seeders;

use App\Enums\TimeoffPolicyType;
use App\Models\Company;
use App\Models\TimeoffQuota;
use App\Models\User;
use Illuminate\Database\Seeder;

class TimeoffPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::all()->each(function ($company) {
            // if ($company->id == 1) {

            // $timeoffPolicy = $company->timeoffPolicies()->create([
            //     'name' => 'Sick Without Certificate',
            //     'code' => 'SWC',
            //     'type' => TimeoffPolicyType::SICK_WITHOUT_CERTIFICATE,
            //     'effective_date' => date('Y-m-d'),
            //     'default_quota' => 2
            // ]);

            $company->timeoffPolicies()->createMany([
                [
                    'name' => 'Annual Leave',
                    'code' => 'L',
                    'type' => TimeoffPolicyType::ANNUAL_LEAVE,
                    'effective_date' => date('Y-m-d'),
                    'is_allow_halfday' => true,
                    'max_consecutively_day' => 5,
                ],
                [
                    'name' => 'Day Off',
                    'code' => 'DO',
                    'type' => TimeoffPolicyType::DAY_OFF,
                    'effective_date' => date('Y-m-d'),
                ],
                [
                    'name' => 'Permission',
                    'code' => 'P',
                    'type' => TimeoffPolicyType::PERMISSION,
                    'effective_date' => date('Y-m-d'),
                    'is_allow_halfday' => true,
                ],
                [
                    'name' => 'Sick Without Certificate',
                    'code' => 'SWC',
                    'type' => TimeoffPolicyType::SICK_WITHOUT_CERTIFICATE,
                    'effective_date' => date('Y-m-d'),
                    'default_quota' => 2
                ],
                [
                    'name' => 'Sick With Certificate',
                    'code' => 'SDC',
                    'type' => TimeoffPolicyType::SICK_WITH_CERTIFICATE,
                    'effective_date' => date('Y-m-d'),
                ],
                [
                    'name' => 'Unpaid Leave',
                    'code' => 'UL',
                    'type' => TimeoffPolicyType::UNPAID_LEAVE,
                    'effective_date' => date('Y-m-d'),
                ],
                [
                    'name' => 'Kematian suami/isteri, orangtua/mertua, anak/menantu (2 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => date('Y-m-d'),
                    'block_leave_take_days' => 2
                ],
                [
                    'name' => 'Kematian anggota keluarga dalam satu rumah (1 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => date('Y-m-d'),
                    'block_leave_take_days' => 1
                ],
                [
                    'name' => 'Pernikahan karyawan (3 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => date('Y-m-d'),
                    'block_leave_take_days' => 3
                ],
                [
                    'name' => 'Pernikahan anak karyawan (2 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => date('Y-m-d'),
                    'block_leave_take_days' => 2
                ],
                [
                    'name' => 'Khitanan/pembaptisan anak (2 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => date('Y-m-d'),
                    'block_leave_take_days' => 2
                ],
                [
                    'name' => 'Istri melahirkan/keguguran kandungan (2 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => date('Y-m-d'),
                    'block_leave_take_days' => 2
                ],
                [
                    'name' => 'Pregnancy Leave',
                    'code' => 'PL',
                    'type' => TimeoffPolicyType::MATERNITY_LEAVE,
                    'effective_date' => date('Y-m-d'),
                ],
                // [
                //     'name' => 'Free Leave',
                //     'code' => 'FL',
                //     'type' => TimeoffPolicyType::FREE_LEAVE,
                //     'effective_date' => date('Y-m-d'),
                // ],
                // [
                //     'name' => 'External Assignment',
                //     'code' => 'E',
                //     'type' => TimeoffPolicyType::ANNUAL_LEAVE,
                //     'effective_date' => date('Y-m-d'),
                // ],
                // [
                //     'name' => 'Extra Off',
                //     'code' => 'EO',
                //     'type' => TimeoffPolicyType::ANNUAL_LEAVE,
                //     'effective_date' => date('Y-m-d'),
                // ],
                // [
                //     'name' => 'Half Leave End',
                //     'code' => 'HLE',
                //     'type' => TimeoffPolicyType::ANNUAL_LEAVE,
                //     'effective_date' => date('Y-m-d'),
                // ],
                // [
                //     'name' => 'Half Leave Start',
                //     'code' => 'HLS',
                //     'type' => TimeoffPolicyType::ANNUAL_LEAVE,
                //     'effective_date' => date('Y-m-d'),
                // ],
                // [
                //     'name' => 'Massive Leave',
                //     'code' => 'MS',
                //     'type' => TimeoffPolicyType::ANNUAL_LEAVE,
                //     'effective_date' => date('Y-m-d'),
                // ],
                // [
                //     'name' => 'Permission',
                //     'code' => 'PR',
                //     'type' => TimeoffPolicyType::ANNUAL_LEAVE,
                //     'effective_date' => date('Y-m-d'),
                // ],
            ]);

            $timeoffPolicy = $company->timeoffPolicies()->where('type', TimeoffPolicyType::SICK_WITHOUT_CERTIFICATE)->first();

            if ($timeoffPolicy) {
                $users = User::where('company_id', $company->id)->get(['id']);
                foreach ($users as $user) {
                    $timeoffQuota = TimeoffQuota::create([
                        'timeoff_policy_id' => $timeoffPolicy->id,
                        'user_id' => $user->id,
                        'effective_start_date' => $timeoffPolicy->effective_date,
                        'quota' => $timeoffPolicy->default_quota,
                    ]);

                    $timeoffQuota->timeoffQuotaHistories()->create([
                        'user_id' => $user->id,
                        'is_increment' => true,
                        'new_balance' => $timeoffQuota->quota,
                    ]);
                }
            }
            // } else {
            //     $company->timeoffPolicies()->create([
            //         'name' => 'timeoff policy ' . $company->name,
            //         'type' => TimeoffPolicyType::ANNUAL_LEAVE,
            //         'effective_date' => date('Y-m-d'),
            //     ]);
            // }
        });
    }
}
