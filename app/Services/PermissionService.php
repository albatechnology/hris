<?php

namespace App\Services;

use App\Models\Permission;

class PermissionService
{
    public static function getAllPermissions()
    {
        return collect(static::permissions())
            ->mergeRecursive(static::administratorPermissions());
    }

    public static function getPermissionsData(?array $persmissions = null): array
    {
        if (!is_null($persmissions) && is_array($persmissions) && count($persmissions) > 0) {
            $persmissions = self::getAllPermissions();
        }

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
            'national_holiday_access' => [
                // 'national_holiday_read',
                'national_holiday_create',
                'national_holiday_edit',
                'national_holiday_delete',
            ],
        ];
    }

    public static function administratorPermissions(): array
    {
        return [
            'user_access' => [
                'user_read',
                'user_create',
                'user_edit',
                'user_delete',
            ],
            'group_access' => [
                'group_read',
                'group_create',
                'group_edit',
                'group_delete',
            ],
            'company_access' => [
                'company_read',
                'company_create',
                'company_edit',
                'company_delete',
            ],
            'branch_access' => [
                'branch_read',
                'branch_create',
                'branch_edit',
                'branch_delete',
            ],
            'division_access' => [
                'division_read',
                'division_create',
                'division_edit',
                'division_delete',
            ],
            'position_access' => [
                'position_read',
                'position_create',
                'position_edit',
                'position_delete',
            ],
            'department_access' => [
                'department_read',
                'department_create',
                'department_edit',
                'department_delete',
            ],
            'role_access' => [
                'role_read',
                'role_create',
                'role_edit',
                'role_delete',
            ],
            'shift_access' => [
                'shift_read',
                'shift_create',
                'shift_edit',
                'shift_delete',
            ],
            'schedule_access' => [
                'schedule_read',
                'schedule_create',
                'schedule_edit',
                'schedule_delete',
            ],
            'attendance_access' => [
                'attendance_read',
                'attendance_create',
                'attendance_edit',
                'attendance_delete',
            ],
            'timeoff_access' => [
                'timeoff_read',
                'timeoff_create',
                'timeoff_edit',
                'timeoff_delete',
            ],
            'timeoff_regulation_access' => [
                'timeoff_regulation_read',
                'timeoff_regulation_create',
                'timeoff_regulation_edit',
                'timeoff_regulation_delete',
            ],
            'payroll_access' => [
                'payroll_read',
                'payroll_create',
                'payroll_edit',
                'payroll_delete',
            ],
            'overtime_access' => [
                'overtime_read',
                'overtime_create',
                'overtime_edit',
                'overtime_delete',
            ],
            'overtime_request_access' => [
                'overtime_request_read',
                'overtime_request_create',
                'overtime_request_edit',
                'overtime_request_delete',
            ],
            'advanced_leave_request_access' => [
                'advanced_leave_request_read',
                'advanced_leave_request_create',
                'advanced_leave_request_edit',
                'advanced_leave_request_delete',
            ],
            'timeoff_policy_access' => [
                'timeoff_policy_read',
                'timeoff_policy_create',
                'timeoff_policy_edit',
                'timeoff_policy_delete',
            ],
            'live_attendance_access' => [
                'live_attendance_read',
                'live_attendance_create',
                'live_attendance_edit',
                'live_attendance_delete',
            ],
            'event_access' => [
                'event_read',
                'event_create',
                'event_edit',
                'event_delete',
            ],
            'custom_field_access' => [
                'custom_field_read',
                'custom_field_create',
                'custom_field_edit',
                'custom_field_delete',
            ],
            'national_holiday_access' => [
                'national_holiday_read',
                // 'national_holiday_create',
                // 'national_holiday_edit',
                // 'national_holiday_delete',
            ],
            // 'supervisor_type_access' => [
            //     'supervisor_type_read',
            //     'supervisor_type_create',
            //     'supervisor_type_edit',
            //     'supervisor_type_delete',
            // ],
        ];
    }

    public static function userPermissions(): array
    {
        return [
            'user_access' => [
                'user_read',
                'user_create',
                'user_edit',
                // 'user_delete',
            ],
            // 'group_access' => [
            //     'group_read',
            //     'group_create',
            //     'group_edit',
            //     'group_delete',
            // ],
            'company_access' => [
                'company_read',
                // 'company_create',
                // 'company_edit',
                // 'company_delete',
            ],
            'branch_access' => [
                'branch_read',
                // 'branch_create',
                // 'branch_edit',
                // 'branch_delete',
            ],
            'division_access' => [
                'division_read',
                // 'division_create',
                // 'division_edit',
                // 'division_delete',
            ],
            'position_access' => [
                'position_read',
                // 'position_create',
                // 'position_edit',
                // 'position_delete',
            ],
            'department_access' => [
                'department_read',
                // 'department_create',
                // 'department_edit',
                // 'department_delete',
            ],
            'role_access' => [
                'role_read',
                // 'role_create',
                // 'role_edit',
                // 'role_delete',
            ],
            'shift_access' => [
                'shift_read',
                // 'shift_create',
                // 'shift_edit',
                // 'shift_delete',
            ],
            'schedule_access' => [
                'schedule_read',
                // 'schedule_create',
                // 'schedule_edit',
                // 'schedule_delete',
            ],
            'attendance_access' => [
                'attendance_read',
                'attendance_create',
                'attendance_edit',
                'attendance_delete',
            ],
            'timeoff_access' => [
                'timeoff_read',
                'timeoff_create',
                'timeoff_edit',
                'timeoff_delete',
            ],
            'timeoff_regulation_access' => [
                'timeoff_regulation_read',
                // 'timeoff_regulation_create',
                // 'timeoff_regulation_edit',
                // 'timeoff_regulation_delete',
            ],
            'payroll_access' => [
                'payroll_read',
                // 'payroll_create',
                // 'payroll_edit',
                // 'payroll_delete',
            ],
            'overtime_access' => [
                'overtime_read',
                'overtime_create',
                'overtime_edit',
                'overtime_delete',
            ],
            'overtime_request_access' => [
                'overtime_request_read',
                'overtime_request_create',
                'overtime_request_edit',
                'overtime_request_delete',
            ],
            'advanced_leave_request_access' => [
                'advanced_leave_request_read',
                'advanced_leave_request_create',
                'advanced_leave_request_edit',
                'advanced_leave_request_delete',
            ],
            'timeoff_policy_access' => [
                'timeoff_policy_read',
                // 'timeoff_policy_create',
                // 'timeoff_policy_edit',
                // 'timeoff_policy_delete',
            ],
            'live_attendance_access' => [
                'live_attendance_read',
                // 'live_attendance_create',
                // 'live_attendance_edit',
                // 'live_attendance_delete',
            ],
            'event_access' => [
                'event_read',
                // 'event_create',
                // 'event_edit',
                // 'event_delete',
            ],
            'custom_field_access' => [
                'custom_field_read',
                // 'custom_field_create',
                // 'custom_field_edit',
                // 'custom_field_delete',
            ],
            'national_holiday_access' => [
                'national_holiday_read',
                // 'national_holiday_create',
                // 'national_holiday_edit',
                // 'national_holiday_delete',
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
                    'parent_id' => $headSubPermissions->id,
                ]);

                self::generateChilds($hsp, $permission);
            } else {
                $hsp = Permission::firstOrCreate([
                    'name' => $permission,
                    // 'guard_name' => $guard,
                    'parent_id' => $headSubPermissions->id,
                ]);
            }
        });
    }

    /**
     * filter permissions ids
     */
    public static function getPermissionNames(array $permissionIds = []): array
    {
        $pids = [];
        if (!is_array($permissionIds) || count($permissionIds) <= 0) {
            return $pids;
        }

        foreach ($permissionIds as $id) {
            $permission = Permission::find($id, ['id', 'name']);
            if ($permission) {
                $pids[] = $permission->name;

                $permissionNames = self::getRelatedPermissions($permission->name);
                array_push($pids, ...$permissionNames);
            }
        }

        return $pids;
    }

    public static function getRelatedPermissions(string $permission): array
    {
        return match ($permission) {
            'receive_order_access' => ['stock_read'],
            'stock_access' => ['product_category_read', 'product_brand_read', 'warehouse_read'],
            'sales_order_access' => ['product_unit_read', 'warehouse_read', 'user_access'],
            'delivery_order_access' => ['sales_order_read'],
            'product_access' => ['product_category_read', 'product_brand_read', 'product_unit_read'],
            'user_access' => ['role_read'],
            default => [],
        };
    }

    public static function getMyPermissions()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $allPermissions = [];
        if ($user->is_super_admin) {
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
