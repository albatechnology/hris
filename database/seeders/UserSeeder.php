<?php

namespace Database\Seeders;

use App\Enums\SettingKey;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Group;
use App\Models\Position;
use App\Models\Role;
use App\Models\Setting;
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
            'email_verified_at' => now(),
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
            'email_verified_at' => now(),
            'password' => self::PASSWORD,
            'type' => UserType::ADMIN,
        ]);
        $administrator->payrollInfo()->create([]);
        $administrator->detail()->create([]);
        DB::table('model_has_roles')->insert([
            'role_id' => $administratorRole->id,
            'model_type' => get_class($administrator),
            'model_id' => $administrator->id,
            'group_id' => $group->id,
        ]);

        // $group = Group::findOrFail(2);
        // $administrator = User::create([
        //     'group_id' => $group->id,
        //     'company_id' => null,
        //     'branch_id' => null,
        //     'name' => 'Administrator - ' . $group->name,
        //     'email' => 'administrator.alba@gmail.com',
        //     'email_verified_at' => now(),
        //     'password' => self::PASSWORD,
        //     'type' => UserType::ADMIN,
        // ]);
        // $administrator->payrollInfo()->create([]);
        // $administrator->detail()->create([]);
        // DB::table('model_has_roles')->insert([
        //     'role_id' => $administratorRole->id,
        //     'model_type' => get_class($administrator),
        //     'model_id' => $administrator->id,
        //     'group_id' => $group->id,
        // ]);

        // $group = Group::findOrFail(3);
        // $administrator = User::create([
        //     'group_id' => $group->id,
        //     'company_id' => null,
        //     'branch_id' => null,
        //     'name' => 'Administrator Patroli' . $group->name,
        //     'email' => 'administrator.patroli@gmail.com',
        //     'email_verified_at' => now(),
        //     'password' => self::PASSWORD,
        //     'type' => UserType::ADMIN,
        // ]);
        // $administrator->payrollInfo()->create([]);
        // $administrator->detail()->create([]);
        // DB::table('model_has_roles')->insert([
        //     'role_id' => $administratorRole->id,
        //     'model_type' => get_class($administrator),
        //     'model_id' => $administrator->id,
        //     'group_id' => $group->id,
        // ]);

        /** ================================================================= */
        Company::all()->each(function (Company $company) {
            $adminRole = Role::create([
                'group_id' => $company->group_id,
                'name' => 'Role Admin ' . $company->name,
            ]);
            $permissions = PermissionService::getPermissionsData(PermissionService::administratorPermissions());
            $adminRole->syncPermissions($permissions);

            $liveAttendanceId = $company->liveAttendances()->first()?->id;
            $admin = User::create([
                'group_id' => $company->group_id,
                'company_id' => $company->id,
                'branch_id' => null,
                'live_attendance_id' => $liveAttendanceId,
                'name' => 'Admin ' . $company->name,
                'email' => 'admin' . $company->id . '@gmail.com',
                'email_verified_at' => now(),
                'password' => self::PASSWORD,
                'type' => UserType::USER,
                'nik' => rand(16, 100),
                'sign_date' => date('Y') . '-01-01',
                'join_date' => date('Y') . '-01-01',
            ]);
            $admin->payrollInfo()->create([
                'basic_salary' => 10000000
            ]);
            $admin->userBpjs()->create([
                'upah_bpjs_kesehatan' => 10000000,
                'upah_bpjs_ketenagakerjaan' => 10000000,
            ]);
            $admin->detail()->create([]);
            DB::table('model_has_roles')->insert([
                'role_id' => $adminRole->id,
                'model_type' => get_class($admin),
                'model_id' => $admin->id,
                'group_id' => $company->group_id,
            ]);
            $admin->companies()->create(['company_id' => $admin->company_id]);

            // set positions
            $admin->positions()->create([
                'department_id' => Department::whereHas('division', fn($q) => $q->where('company_id', $admin->company_id))->where('name', 'HR')->firstOrFail(['id'])->id,
                'position_id' => Position::where('company_id', $admin->company_id)->where('name', 'Manager')->firstOrFail(['id'])->id,
            ]);

            Setting::where('key', SettingKey::REQUEST_APPROVER)->where('company_id', $admin->company_id)->update(['value' => $admin->id]);

            /** ================================================================= */
            $userRole = Role::create([
                'group_id' => $company->group_id,
                'name' => 'Role User ' . $company->name,
            ]);
            $permissions = PermissionService::getPermissionsData(PermissionService::userPermissions());
            $userRole->syncPermissions($permissions);


            // (new UserSunImport)->import(public_path('import_users.xlsx'));

            $company->branches->each(function (Branch $branch) use ($company, $userRole, $admin, $liveAttendanceId) {
                $admin->branches()->create(['branch_id' => $branch->id]);

                if ($company->group_id == 1) {
                    $albaUsers = [
                        [
                            'name' => 'Nikko Febika',
                            'email' => 'febika.nikko@gmail.com',
                            'image' => public_path('img/nikko.jpg')
                        ],
                        [
                            'name' => 'Ibnul Mundzir',
                            'email' => 'ibnulmundzir97@gmail.com',
                            'image' => public_path('img/ibnul-patroli.jpeg')
                        ],
                        [
                            'name' => 'Masfud Difa Pratama',
                            'email' => 'masfuddifapratama@gmail.com',
                            'image' => public_path('img/difa.jpg')
                        ],
                        [
                            'name' => 'Muhammad Robbi Zulfikar',
                            'email' => 'mrobbizulfikar@gmail.com',
                            'image' => public_path('img/zulfi.jpg')
                        ],
                        [
                            'name' => 'Poedi Udi Maurif',
                            'email' => 'poedi1612@gmail.com',
                            'image' => public_path('img/poedi.jpg')
                        ],
                        [
                            'name' => 'Teuku Banta Karollah',
                            'email' => 'bantakarollah@gmail.com',
                            'image' => public_path('img/banta.jpg')
                        ],
                        [
                            'name' => 'Urinaldi Sri Juliandika',
                            'email' => 'aldynsx@gmail.com',
                            'image' => public_path('img/aldi.jpg')
                        ],
                    ];

                    foreach ($albaUsers as $i => $albaUser) {
                        $user = $branch->users()->create([
                            // 'approval_id' => $admin->id,
                            // 'parent_id' => $admin->id,
                            'live_attendance_id' => $liveAttendanceId,
                            'name' => $albaUser['name'],
                            'email' => $albaUser['email'],
                            'email_verified_at' => now(),
                            'password' => self::PASSWORD,
                            'type' => UserType::USER,
                            'nik' => rand(16, 100),
                            'phone' => "08569197717$i",
                            'sign_date' => date('Y') . '-01-01',
                            'join_date' => date('Y') . '-01-01',
                        ]);

                        $user->addMedia($albaUser['image'])->preservingOriginal()->toMediaCollection('user');

                        $user->payrollInfo()->create([
                            'basic_salary' => 10000000
                        ]);
                        $user->userBpjs()->create([
                            'upah_bpjs_kesehatan' => 10000000,
                            'upah_bpjs_ketenagakerjaan' => 10000000,
                        ]);
                        $user->detail()->create([]);
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
                } else {
                    // for ($i = 1; $i < 4; $i++) {
                    //     /** @var User $user */
                    //     $user = $branch->users()->create([
                    //         'approval_id' => $admin->id,
                    //         'parent_id' => $admin->id,
                    //         'name' => sprintf('User %s %s', $i, $branch->name),
                    //         'email' => sprintf('user%s.%s@gmail.com', $i, $branch->id),
                    //         'email_verified_at' => now(),
                    //         'password' => self::PASSWORD,
                    //         'type' => UserType::USER,
                    //         'nik' => rand(16, 100),
                    //         'phone' => "08569197717$i",
                    //         'sign_date' => date('Y') . '-01-01',
                    //         'join_date' => date('Y') . '-01-01',
                    //     ]);
                    //     $user->addMedia(public_path('img/difa.jpg'))->preservingOriginal()->toMediaCollection('user');
                    //     $user->payrollInfo()->create([
                    //         'basic_salary' => 10000000
                    //     ]);
                    //     $user->detail()->create([]);
                    //     DB::table('model_has_roles')->insert([
                    //         'role_id' => $userRole->id,
                    //         'model_type' => get_class($user),
                    //         'model_id' => $user->id,
                    //         'group_id' => $company->group_id,
                    //     ]);
                    //     $user->branches()->create(['branch_id' => $user->branch_id]);
                    //     $user->companies()->create(['company_id' => $user->company_id]);
                    //     $user->schedules()->sync($user->company->schedules->pluck('id'));
                    // }
                }
            });
        });
    }
}
