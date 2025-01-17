<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Enums\TimeoffPolicyType;
use App\Models\Company;
use App\Models\TimeoffPolicy;
use App\Models\TimeoffQuota;
use App\Models\TimeoffQuotaHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateTimeoffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        TimeoffPolicy::query()->truncate();
        TimeoffQuota::query()->truncate();
        TimeoffQuotaHistory::query()->truncate();
        Company::all()->each(function ($company) {
            $company->timeoffPolicies()->createMany([
                [
                    'name' => 'Annual Leave',
                    'code' => 'L',
                    'type' => TimeoffPolicyType::TIME_OFF,
                    'effective_date' => "2025-01-01",
                    'is_allow_halfday' => true,
                    'max_consecutively_day' => 5,
                ],
                [
                    'name' => 'Day Off',
                    'code' => 'DO',
                    'type' => TimeoffPolicyType::DAY_OFF,
                    'effective_date' => "2025-01-01",
                ],
                [
                    'name' => 'Permission',
                    'code' => 'P',
                    'type' => TimeoffPolicyType::PERMISSION,
                    'effective_date' => "2025-01-01",
                    'is_allow_halfday' => true,
                ],
                [
                    'name' => 'Sick Without Certificate',
                    'code' => 'SWC',
                    'type' => TimeoffPolicyType::SICK_WITHOUT_CERTIFICATE,
                    'effective_date' => "2025-01-01",
                    'default_quota' => 2
                ],
                [
                    'name' => 'Sick With Certificate',
                    'code' => 'SDC',
                    'type' => TimeoffPolicyType::SICK_WITH_CERTIFICATE,
                    'effective_date' => "2025-01-01",
                ],
                [
                    'name' => 'Unpaid Leave',
                    'code' => 'UL',
                    'type' => TimeoffPolicyType::UNPAID_LEAVE,
                    'effective_date' => "2025-01-01",
                ],
                [
                    'name' => 'Kematian suami/isteri, orangtua/mertua, anak/menantu (2 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => "2025-01-01",
                    'block_leave_take_days' => 2
                ],
                [
                    'name' => 'Kematian anggota keluarga dalam satu rumah (1 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => "2025-01-01",
                    'block_leave_take_days' => 1
                ],
                [
                    'name' => 'Pernikahan karyawan (3 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => "2025-01-01",
                    'block_leave_take_days' => 3
                ],
                [
                    'name' => 'Pernikahan anak karyawan (2 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => "2025-01-01",
                    'block_leave_take_days' => 2
                ],
                [
                    'name' => 'Khitanan/pembaptisan anak (2 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => "2025-01-01",
                    'block_leave_take_days' => 2
                ],
                [
                    'name' => 'Istri melahirkan/keguguran kandungan (2 hari kerja)',
                    'code' => 'FL',
                    'type' => TimeoffPolicyType::FREE_LEAVE,
                    'effective_date' => "2025-01-01",
                    'block_leave_take_days' => 2
                ],
                [
                    'name' => 'Pregnancy Leave',
                    'code' => 'PL',
                    'type' => TimeoffPolicyType::MATERNITY_LEAVE,
                    'effective_date' => "2025-01-01",
                ],
            ]);
        });

        $datas = [
            ['nik' => '313003', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '314003', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '314004', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '315004', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '316004', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '319002', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '315026', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '322003', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '323003', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '323008', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '323010', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324001', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324005', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324012', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324013', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324014', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '218036', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '215033', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '216025', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '219024', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '219034', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '220010', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '220011', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221008', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221021', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222008', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222010', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222017', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223002', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223016', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223026', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223034', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223035', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224006', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224011', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224023', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224026', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224027', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '323007', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '217036', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224019', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '520005', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '110001', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '110002', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '110003', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224022', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223005', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223007', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223008', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '211013', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '219033', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221013', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221014', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223029', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224024', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '525001', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 0]]],
            ['nik' => '525003', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 0]]],
            ['nik' => '310003', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324002', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324003', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324008', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '220007', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '211010', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '210018', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '213003', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '217015', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '219008', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221004', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223015', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224012', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '312004', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '211019', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '214016', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '217029', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '218035', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '219032', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221023', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222011', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '319009', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '319012', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '524003', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222026', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224028', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '213007', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223024', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '415005', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '212004', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '214044', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222023', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223010', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224008', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '524010', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '524014', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '217021', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '220005', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223006', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224017', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222001', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '319014', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '320004', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324009', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324011', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224025', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224021', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '523008', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '524019', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '525002', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 0]]],
            ['nik' => '218006', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '220001', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221018', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223032', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221026', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222015', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223017', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224013', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '312001', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '210032', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '520007', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '524012', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '210015', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '211005', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '213019', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '214052', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '215031', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '217022', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '219003', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '520001', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '520004', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '210023', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '212008', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '213011', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '214023', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '214030', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '218020', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '219012', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221020', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '520006', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '217004', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '217039', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '217040', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '218014', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '218025', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '218038', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '220003', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221024', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '221025', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222012', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222016', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222020', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222027', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '222028', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223001', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223011', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223013', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223014', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223018', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223022', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223023', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223027', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223031', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '223036', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224001', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224004', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224005', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224010', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '224018', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '225001', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 0]]],
            ['nik' => '225002', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 0]]],
            ['nik' => '311003', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '319006', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '319015', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '322005', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '323009', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324006', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '522004', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '522007', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '524006', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '524015', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '524016', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '524017', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '524018', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '525004', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 0]]],
            ['nik' => '525005', 'timeoff' => [['code' => 'L', 'quota' => 0], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 0]]],
            ['nik' => '316003', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '317012', 'timeoff' => [['code' => 'L', 'quota' => 2], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
            ['nik' => '324010', 'timeoff' => [['code' => 'L', 'quota' => 1], ['code' => 'DO', 'quota' => 0], ['code' => 'SWC', 'quota' => 2]]],
        ];

        $niks = [];
        foreach ($datas as $data) {
            $user = User::firstWhere('nik', $data['nik']);
            if ($user) {
                $niks[] = $user->nik;
                foreach ($data['timeoff'] as $timeoff) {
                    $timeoffPolicy = TimeoffPolicy::where('company_id', $user->company_id)->where('code', $timeoff['code'])->first();
                    if ($timeoffPolicy) {
                        $timeoffQuota = TimeoffQuota::create([
                            'timeoff_policy_id' => $timeoffPolicy->id,
                            'user_id' => $user->id,
                            'effective_start_date' => $timeoffPolicy->effective_date,
                            'quota' => $timeoff['quota'],
                        ]);

                        $timeoffQuota->timeoffQuotaHistories()->create([
                            'user_id' => $user->id,
                            'is_increment' => true,
                            'new_balance' => $timeoffQuota->quota,
                        ]);
                    }
                }
            }
        }

        if (count($niks) > 0) {
            $users = User::whereNotIn('nik', $niks)->get();
            foreach ($users as $user) {
                $timeoffPolicies = TimeoffPolicy::where('company_id', $user->company_id)->whereIn('code', ['L', 'DO', 'SWC'])->get();
                foreach ($timeoffPolicies as $timeoffPolicy) {
                    $timeoffQuota = TimeoffQuota::create([
                        'timeoff_policy_id' => $timeoffPolicy->id,
                        'user_id' => $user->id,
                        'effective_start_date' => $timeoffPolicy->effective_date,
                        'quota' => 0,
                    ]);

                    $timeoffQuota->timeoffQuotaHistories()->create([
                        'user_id' => $user->id,
                        'is_increment' => true,
                        'new_balance' => $timeoffQuota->quota,
                    ]);
                }
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
