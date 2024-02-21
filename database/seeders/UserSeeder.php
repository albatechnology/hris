<?php

namespace Database\Seeders;

use App\Enums\BloodType;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create superadmin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => '12345678',
            'type' => UserType::SUPER_ADMIN,
        ]);

        $adminRole = Role::create([
            // 'group_id',
            'name' => 'Administrator',
        ]);
        $permissions = PermissionService::getPermissionsData();
        $adminRole->syncPermissions($permissions);

        $group = Group::findOrFail(1);
        $admin = User::create([
            'group_id' => $group->id,
            'company_id' => null,
            'branch_id' => null,
            'name' => 'Administrator - '.$group->name,
            'email' => 'admin1@gmail.com',
            'password' => '12345678',
            'type' => UserType::ADMINISTRATOR,
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $adminRole->id,
            'model_type' => get_class($admin),
            'model_id' => $admin->id,
            'group_id' => $group->id,
        ]);
        $admin = User::create([
            'group_id' => $group->id,
            'company_id' => null,
            'branch_id' => null,
            'name' => 'Administrator 12 - '.$group->name,
            'email' => 'admin12@gmail.com',
            'password' => '12345678',
            'type' => UserType::ADMINISTRATOR,
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $adminRole->id,
            'model_type' => get_class($admin),
            'model_id' => $admin->id,
            'group_id' => $group->id,
        ]);

        $group = Group::findOrFail(2);
        $admin = User::create([
            'group_id' => $group->id,
            'company_id' => null,
            'branch_id' => null,
            'name' => 'Administrator - '.$group->name,
            'email' => 'admin2@gmail.com',
            'password' => '12345678',
            'type' => UserType::ADMINISTRATOR,
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $adminRole->id,
            'model_type' => get_class($admin),
            'model_id' => $admin->id,
            'group_id' => $group->id,
        ]);

        /** ================================================================= */
        Branch::all()->each(function ($branch) {
            // $faker = \Faker\Factory::create('id_ID');
            for ($i = 1; $i < 4; $i++) {
                /** @var User $user */
                $user = $branch->users()->create([
                    'name' => sprintf('User %s %s', $i, $branch->name),
                    'email' => sprintf('user%s.%s@gmail.com', $i, $branch->id),
                    'password' => '12345678',
                    'type' => UserType::USER,
                    'nik' => rand(16, 100),
                    'phone' => "08569197717$i",
                    // 'birth_place' => 'Jakarta',
                    // 'birthdate' => date('Y-m-d', strtotime('- 20 years')),
                    // 'marital_status' => MaritalStatus::SINGLE,
                    // 'blood_type' => BloodType::A,
                    // 'religion' => Religion::ISLAM
                ]);
                $user->branches()->create(['branch_id' => $user->branch_id]);
                $user->companies()->create(['company_id' => $user->company_id]);
                $user->schedules()->sync($user->company->schedules->pluck('id'));
            }
        });
    }
}
