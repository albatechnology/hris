<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            'type' => UserType::SUPER_ADMIN
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
            'name' => 'Administrator - ' . $group->name,
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

        $group = Group::findOrFail(2);
        $admin = User::create([
            'group_id' => $group->id,
            'company_id' => null,
            'branch_id' => null,
            'name' => 'Administrator - ' . $group->name,
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
    }
}
