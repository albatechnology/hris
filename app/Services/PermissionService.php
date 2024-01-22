<?php

namespace App\Services;

use App\Models\Permission;

class PermissionService
{
    public static function getAllPermissions()
    {
        return collect(static::permissions());
    }

    public static function getPermissionsData() : array
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

    public static function permissions() : array
    {
        return [
            // 'dashboard_access',

            'user_access' => [
                'user_create',
                'user_edit',
                'user_delete',
            ],

            'internal_department_access' => [
                'internal_department_create',
                'internal_department_edit',
                'internal_department_delete',
            ],

            'company_management_access' => [
                'company_access' => [
                    'company_create',
                    'company_edit',
                    'company_delete',
                ],

                'department_access' => [
                    'department_create',
                    'department_edit',
                    'department_delete',
                ],
            ],

            'category_access' => [
                'category_create',
                'category_edit',
                'category_delete',
            ],

            'archive_management_access' => [
                'archive_type_access' => [
                    'archive_type_create',
                    'archive_type_edit',
                    'archive_type_delete',
                ],

                'archive_access' => [
                    'archive_create',
                    'archive_edit',
                    'archive_delete',
                ],
            ],


            'file_access' => [
                'file_create',
                'file_edit',
                'file_delete',
            ],

            'surat_masuk_access' => [
                'surat_masuk_create',
                'surat_masuk_edit',
                'surat_masuk_delete',
            ],

            'surat_keluar_access' => [
                'surat_keluar_create',
                'surat_keluar_edit',
                'surat_keluar_delete',
            ],

            'file_borrow_access' => [
                'file_borrow_create',
                'file_borrow_edit',
                'file_borrow_delete',
            ],

            'approval_access' => [
                'approval_create',
                'approval_edit',
                'approval_delete',
            ],

            'setting_access' => [
                'setting_edit'
            ],
            'role_access' => [
                'role_create',
                'role_edit',
                'role_delete',
            ],
        ];
    }

    public static function userPermissions() : array
    {
        return [
            'file_access',
            'file_create',
            'file_edit',
            'file_delete',

            'surat_masuk_access',
            'surat_masuk_create',
            'surat_masuk_edit',
            'surat_masuk_delete',

            'surat_keluar_access',
            'surat_keluar_create',
            'surat_keluar_edit',
            'surat_keluar_delete',

            'file_borrow_access',
            'file_borrow_create',
            'file_borrow_edit',
            'file_borrow_delete',

            'approval_access',
            'approval_create',
            'approval_edit',
            'approval_delete',
        ];
    }

    public static function visitorPermissions() : array
    {
        return [
            'file_access',
            'file_create',
            'file_edit',
            // 'file_delete',

            'surat_masuk_access',
            // 'surat_masuk_create',
            // 'surat_masuk_edit',
            // 'surat_masuk_delete',

            'surat_keluar_access',
            // 'surat_keluar_create',
            // 'surat_keluar_edit',
            // 'surat_keluar_delete',

            'file_borrow_access',
            'file_borrow_create',
            'file_borrow_edit',
            // 'file_borrow_delete',

            'approval_access',
            // 'approval_create',
            'approval_edit',
            // 'approval_delete',
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
