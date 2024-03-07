<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    const PASSWORD = '12345678';
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create superadmin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => self::PASSWORD,
            'type' => UserType::SUPER_ADMIN,
        ]);

        $administratorRole = Role::create([
            // 'group_id',
            'name' => 'Administrator',
        ]);
        $permissions = PermissionService::getPermissionsData(PermissionService::administratorPermissions());
        $administratorRole->syncPermissions($permissions);

        $group = Group::findOrFail(1);
        $administrator = User::create([
            'group_id' => $group->id,
            'company_id' => null,
            'branch_id' => null,
            'name' => 'Administrator - ' . $group->name,
            'email' => 'administrator1@gmail.com',
            'password' => self::PASSWORD,
            'type' => UserType::ADMINISTRATOR,
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $administratorRole->id,
            'model_type' => get_class($administrator),
            'model_id' => $administrator->id,
            'group_id' => $group->id,
        ]);
        $administrator = User::create([
            'group_id' => $group->id,
            'company_id' => null,
            'branch_id' => null,
            'name' => 'Administrator 12 - ' . $group->name,
            'email' => 'administrator12@gmail.com',
            'password' => self::PASSWORD,
            'type' => UserType::ADMINISTRATOR,
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $administratorRole->id,
            'model_type' => get_class($administrator),
            'model_id' => $administrator->id,
            'group_id' => $group->id,
        ]);

        $group = Group::findOrFail(2);
        $administrator = User::create([
            'group_id' => $group->id,
            'company_id' => null,
            'branch_id' => null,
            'name' => 'Administrator - ' . $group->name,
            'email' => 'administrator2@gmail.com',
            'password' => self::PASSWORD,
            'type' => UserType::ADMINISTRATOR,
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $administratorRole->id,
            'model_type' => get_class($administrator),
            'model_id' => $administrator->id,
            'group_id' => $group->id,
        ]);

        /** ================================================================= */
        Company::all()->each(function (Company $company) {
            $adminRole = Role::create([
                'group_id' => $company->group_id,
                'name' => 'Role Admin ' . $company->name,
            ]);
            $permissions = PermissionService::getPermissionsData(PermissionService::administratorPermissions());
            $adminRole->syncPermissions($permissions);

            $admin = User::create([
                'group_id' => $company->group_id,
                'company_id' => null,
                'branch_id' => null,
                'name' => 'Admin ' . $company->name,
                'email' => 'admin' . $company->id . '@gmail.com',
                'password' => self::PASSWORD,
                'type' => UserType::USER,
                'nik' => rand(16, 100),
                'sign_date' => date('Y') . '-01-01',
                'join_date' => date('Y') . '-01-01',
            ]);
            DB::table('model_has_roles')->insert([
                'role_id' => $adminRole->id,
                'model_type' => get_class($admin),
                'model_id' => $admin->id,
                'group_id' => $company->group_id,
            ]);

            /** ================================================================= */
            $userRole = Role::create([
                'group_id' => $company->group_id,
                'name' => 'Role User ' . $company->name,
            ]);
            $permissions = PermissionService::getPermissionsData(PermissionService::userPermissions());
            $userRole->syncPermissions($permissions);

            $company->branches->each(function (Branch $branch) use ($company, $userRole) {
                // $faker = \Faker\Factory::create('id_ID');
                for ($i = 1; $i < 4; $i++) {
                    /** @var User $user */
                    $user = $branch->users()->create([
                        'name' => sprintf('User %s %s', $i, $branch->name),
                        'email' => sprintf('user%s.%s@gmail.com', $i, $branch->id),
                        'password' => self::PASSWORD,
                        'type' => UserType::USER,
                        'nik' => rand(16, 100),
                        'phone' => "08569197717$i",
                        'sign_date' => date('Y') . '-01-01',
                        'join_date' => date('Y') . '-01-01',
                    ]);
                    DB::table('model_has_roles')->insert([
                        'role_id' => $userRole->id,
                        'model_type' => get_class($user),
                        'model_id' => $user->id,
                        'group_id' => $company->group_id,
                    ]);
                    $user->branches()->create(['branch_id' => $user->branch_id]);
                    $user->companies()->create(['company_id' => $user->company_id]);
                    $user->schedules()->sync($user->company->schedules->pluck('id'));
                }
            });
        });
    }
}
