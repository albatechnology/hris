<?php

namespace App\Services;

use App\Models\Permission;

class PermissionServiceBackup
{
    public static function getAllPermissions()
    {
        return collect(static::permissions());
    }

    public static function getPermissionsData(): array
    {
        $persmissions = self::permissions();

        $data = [];
        foreach ($persmissions as $key => $persmission) {
            if (is_array($persmission)) {
                $data[] = $key;
                foreach ($persmission as $key => $persmission) {
                    if (is_array($persmission)) {
                        $data[] = $key;

                        foreach ($persmission as $p) {
                            $data[] = $p;
                        }
                    } else {
                        $data[] = $persmission;
                    }
                }
            } else {
                $data[] = $persmission;
            }
        }
        return $data;
    }

    public static function permissions(): array
    {
        return [
            'dashboard_access',

            'user_management_access' => [
                'role_access' => [
                    'role_create',
                    'role_edit',
                    'role_delete',
                ],

                'user_access' => [
                    'user_create',
                    'user_edit',
                    'user_delete',
                ],
            ],

            'file_management_access' => [
                'file_access' => [
                    'file_create',
                    'file_edit',
                    'file_delete',
                ],

                'category_access' => [
                    'category_create',
                    'category_edit',
                    'category_delete',
                ],

                'file_tree_access' => [
                    'file_tree_create',
                    'file_tree_edit',
                    'file_tree_delete',
                ],
            ],

            'department_access' => [
                'department_create',
                'department_edit',
                'department_delete',
            ],

            'setting_access',
        ];
    }

    public static function generateChilds(Permission $headSubPermissions, array $subPermissions)
    {
        collect($subPermissions)->each(function ($permission, $key) use ($headSubPermissions) {
            if (is_array($permission)) {
                $hsp = Permission::firstOrCreate([
                    'name' => $key,
                    // 'guard_name' => $guard,
                    'parent_id' => $headSubPermissions->id
                ]);

                self::generateChilds($hsp, $permission);
            } else {
                $hsp = Permission::firstOrCreate([
                    'name' => $permission,
                    // 'guard_name' => $guard,
                    'parent_id' => $headSubPermissions->id
                ]);
            }

            return;
        });
    }
}
