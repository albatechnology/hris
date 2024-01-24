<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $permissions = PermissionService::getAllPermissions();

        $permissions->each(function ($permission, $key) {
            if (is_array($permission)) {
                $headSubPermissions = Permission::firstOrCreate([
                    'name' => $key,
                ]);

                PermissionService::generateChilds($headSubPermissions, $permission);
            } else {
                Permission::firstOrCreate([
                    'name' => $permission,
                ]);
            }
        });
    }
}
