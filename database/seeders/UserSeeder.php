<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Company;
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
        $permissions = PermissionService::getAllPermissions();
        $adminRole->syncPermissions($permissions);

        $company = Company::findOrFail(1);
        $admin = User::create([
            'name' => 'Administrator - ' . $company->name,
            'email' => 'admin1@gmail.com',
            'password' => '12345678',
            'type' => UserType::ADMINISTRATOR
        ]);
        // $admin->assignRole($adminRole);
        DB::table('model_has_roles')->insert([
            'role_id' => $adminRole->id,
            'model_type' => get_class($admin),
            'model_id' => $admin->id,
            'group_id' => $company->group->id,
        ]);

        $company = Company::findOrFail(2);
        $admin = User::create([
            'name' => 'Administrator - ' . $company->name,
            'email' => 'admin2@gmail.com',
            'password' => '12345678',
            'type' => UserType::ADMINISTRATOR
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $adminRole->id,
            'model_type' => get_class($admin),
            'model_id' => $admin->id,
            'group_id' => $company->group->id,
        ]);

        $company = Company::findOrFail(2);
        $admin = User::create([
            'name' => 'Administrator - ' . $company->name,
            'email' => 'admin3@gmail.com',
            'password' => '12345678',
            'type' => UserType::ADMINISTRATOR
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $adminRole->id,
            'model_type' => get_class($admin),
            'model_id' => $admin->id,
            'group_id' => $company->group->id,
        ]);
    }
}
