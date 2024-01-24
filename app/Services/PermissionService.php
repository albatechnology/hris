<?php

namespace App\Services;

use App\Models\Permission;

class PermissionService
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

            'user_access' => [
                'user_create',
                'user_edit',
                'user_delete',
            ],
            'group_access' => [
                'group_create',
                'group_edit',
                'group_delete',
            ],
            'company_access' => [
                'company_create',
                'company_edit',
                'company_delete',
            ],
            'branch_access' => [
                'branch_create',
                'branch_edit',
                'branch_delete',
            ],
            'division_access' => [
                'division_create',
                'division_edit',
                'division_delete',
            ],
            'position_access' => [
                'position_create',
                'position_edit',
                'position_delete',
            ],
            'department_access' => [
                'department_create',
                'department_edit',
                'department_delete',
            ],
            'role_access' => [
                'role_create',
                'role_edit',
                'role_delete',
            ],
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

    public static function getMyPermissions()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $allPermissions = [];
        if ($user->hasRole('admin')) {
            foreach (self::getAllPermissions() as $parent => $childs) {
                if (is_array($childs)) {
                    $allPermissions[$parent][$parent] = true;
                    foreach ($childs as $child) {
                        $allPermissions[$parent][$child] = true;
                    }
                } else {
                    $allPermissions[$childs] = true;
                }
            }
        } else {
            $myPermissions = $user?->getAllPermissions()?->pluck('name') ?? collect([]);
            foreach (self::getAllPermissions() as $parent => $childs) {
                if (is_array($childs)) {
                    $allPermissions[$parent][$parent] = $myPermissions->search($parent) === false ? false : true;
                    foreach ($childs as $child) {
                        $allPermissions[$parent][$child] = $myPermissions->search($child) === false ? false : true;
                    }
                } else {
                    $allPermissions[$childs] = $myPermissions->search($childs) === false ? false : true;
                }
            }
        }

        return $allPermissions;
    }
}
