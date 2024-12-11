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
                'national_holiday_read',
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
                'user_set_supervisor',
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
            'time_management_access' => [
                'time_management_read',
                'time_management_create',
                'time_management_edit',
                'time_management_delete',
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
            'payroll_schedule_access' => [
                'payroll_schedule_read',
                'payroll_schedule_create',
                'payroll_schedule_edit',
                'payroll_schedule_delete',
            ],
            'cut_off_and_tax_setting_access' => [
                'cut_off_and_tax_setting_read',
                'cut_off_and_tax_setting_create',
                'cut_off_and_tax_setting_edit',
                'cut_off_and_tax_setting_delete',
            ],
            'payroll_component_access' => [
                'payroll_component_read',
                'payroll_component_create',
                'payroll_component_edit',
                'payroll_component_delete',
            ],
            'payroll_setting_access' => [
                'payroll_setting_read',
                'payroll_setting_create',
                'payroll_setting_edit',
                'payroll_setting_delete',
            ],
            'run_payroll_access' => [
                'run_payroll_read',
                'run_payroll_create',
                'run_payroll_edit',
                'run_payroll_delete',
            ],
            'pro_rate_setting_access' => [
                'pro_rate_setting_read',
                'pro_rate_setting_create',
                'pro_rate_setting_edit',
                'pro_rate_setting_delete',
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
            'task_request_access' => [
                'task_request_read',
                'task_request_create',
                'task_request_edit',
                'task_request_delete',
            ],
            'advanced_leave_request_access' => [
                'advanced_leave_request_read',
                'advanced_leave_request_create',
                'advanced_leave_request_edit',
                'advanced_leave_request_delete',
            ],
            'request_change_data_access' => [
                'request_change_data_read',
                'request_change_data_create',
            ],
            'request_change_data_allowes_access' => [
                'request_change_data_allowes_read',
                'request_change_data_allowes_edit',
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
            'supervisor_type_access' => [
                'supervisor_type_read',
                'supervisor_type_create',
                'supervisor_type_edit',
                'supervisor_type_delete',
            ],
            'task_access' => [
                'task_read',
                'task_create',
                'task_edit',
                'task_delete',
            ],
            'user_transfer_access' => [
                'user_transfer_read',
                'user_transfer_create',
                'user_transfer_edit',
                'user_transfer_delete',
            ],
            'announcement_access' => [
                'announcement_read',
                'announcement_create',
                'announcement_edit',
                'announcement_delete',
            ],
            'incident_access' => [
                'incident_read',
                'incident_create',
                'incident_edit',
                'incident_delete',
            ],
            'client_access' => [
                'client_read',
                'client_create',
                'client_edit',
                'client_delete',
            ],
            'patrol_access' => [
                'patrol_read',
                'patrol_create',
                'patrol_edit',
                'patrol_delete',
            ],
            'setting_access' => [
                'setting_read',
                'setting_edit',
            ],
        ];
    }

    public static function userPermissions(): array
    {
        return [
            'user_access' => [
                'user_read',
                // 'user_create',
                // 'user_edit',
                // 'user_delete',
            ],
            'group_access' => [
                'group_read',
                // 'group_create',
                // 'group_edit',
                // 'group_delete',
            ],
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
            'time_management_access' => [
                'time_management_read',
                'time_management_create',
                'time_management_edit',
                // 'time_management_delete',
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
            'payroll_schedule_access' => [
                'payroll_schedule_read',
                // 'payroll_schedule_create',
                // 'payroll_schedule_edit',
                // 'payroll_schedule_delete',
            ],
            'cut_off_and_tax_setting_access' => [
                'cut_off_and_tax_setting_read',
                // 'cut_off_and_tax_setting_create',
                // 'cut_off_and_tax_setting_edit',
                // 'cut_off_and_tax_setting_delete',
            ],
            'payroll_component_access' => [
                'payroll_component_read',
                'payroll_component_create',
                'payroll_component_edit',
                'payroll_component_delete',
            ],
            'payroll_setting_access' => [
                'payroll_setting_read',
                // 'payroll_setting_create',
                // 'payroll_setting_edit',
                // 'payroll_setting_delete',
            ],
            'run_payroll_access' => [
                'run_payroll_read',
                // 'run_payroll_create',
                // 'run_payroll_edit',
                // 'run_payroll_delete',
            ],
            'pro_rate_setting_access' => [
                'pro_rate_setting_read',
                // 'pro_rate_setting_create',
                // 'pro_rate_setting_edit',
                // 'pro_rate_setting_delete',
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
            'task_request_access' => [
                'task_request_read',
                'task_request_create',
                'task_request_edit',
                'task_request_delete',
            ],
            'advanced_leave_request_access' => [
                'advanced_leave_request_read',
                'advanced_leave_request_create',
                'advanced_leave_request_edit',
                'advanced_leave_request_delete',
            ],
            'request_change_data_access' => [
                'request_change_data_read',
                'request_change_data_create',
            ],
            'request_change_data_allowes_access' => [
                'request_change_data_allowes_read',
                'request_change_data_allowes_edit',
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
            'supervisor_type_access' => [
                'supervisor_type_read',
                // 'supervisor_type_create',
                // 'supervisor_type_edit',
                // 'supervisor_type_delete',
            ],
            'task_access' => [
                'task_read',
                'task_create',
                'task_edit',
                'task_delete',
            ],
            'user_transfer_access' => [
                'user_transfer_read',
                // 'user_transfer_create',
                // 'user_transfer_edit',
                // 'user_transfer_delete',
            ],
            'announcement_access' => [
                'announcement_read',
                // 'announcement_create',
                // 'announcement_edit',
                // 'announcement_delete',
            ],
            'incident_access' => [
                'incident_read',
                'incident_create',
                'incident_edit',
                'incident_delete',
            ],
            'client_access' => [
                'client_read',
                // 'client_create',
                // 'client_edit',
                // 'client_delete',
            ],
            'patrol_access' => [
                'patrol_read',
                // 'patrol_create',
                // 'patrol_edit',
                // 'patrol_delete',
            ],
            'setting_access' => [
                'setting_read',
                // 'setting_edit',
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
    // public static function getPermissionNames(array $permissionIds = []): array
    // {
    //     $pids = [];
    //     if (!is_array($permissionIds) || count($permissionIds) <= 0) {
    //         return $pids;
    //     }

    //     foreach ($permissionIds as $id) {
    //         $permission = Permission::find($id, ['id', 'name']);
    //         if ($permission) {
    //             $pids[] = $permission->name;

    //             $permissionNames = self::getRelatedPermissions($permission->name);
    //             array_push($pids, ...$permissionNames);
    //         }
    //     }

    //     return $pids;
    // }

    // public static function getRelatedPermissions(string $permission): array
    // {
    //     return match ($permission) {
    //         'receive_order_access' => ['stock_read'],
    //         'stock_access' => ['product_category_read', 'product_brand_read', 'warehouse_read'],
    //         'sales_order_access' => ['product_unit_read', 'warehouse_read', 'user_access'],
    //         'delivery_order_access' => ['sales_order_read'],
    //         'product_access' => ['product_category_read', 'product_brand_read', 'product_unit_read'],
    //         'user_access' => ['role_read'],
    //         default => [],
    //     };
    // }

    public static function getMyPermissions()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $allPermissions = [];
        if ($user->is_super_admin || $user->is_admin) {
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

        $allPermissions['navbar']['company']['user_management_access'] = false;
        $allPermissions['navbar']['company']['setting_access'] = false;
        $allPermissions['navbar']['company']['payroll_access'] = false;
        // $allPermissions['navbar']['company']['update_payroll_component_access'] = false;

        foreach ($allPermissions as $parent => $childs) {
            foreach ($childs as $key => $value) {
                if (in_array($key, ['group_access', 'company_access', 'branch_access', 'role_access', 'user_access']) && $value === true) $allPermissions['navbar']['company']['user_management_access'] = true;
                if (in_array($key, ['payroll_schedule_access', 'cut_off_and_tax_setting_access', 'payroll_component_access', 'run_payroll_access', 'pro_rate_setting_access']) && $value === true) $allPermissions['navbar']['company']['payroll_access'] = true;
                if (in_array($key, ['company_access', 'time_management_access', 'timeoff_regulation_access']) && $value === true) $allPermissions['navbar']['company']['setting_access'] = true;
            }
        }

        return $allPermissions;
    }
}

// related permission
// user_read = branch_read


// additional permissions :
// user_set_supervisor
// update_payroll_component_access
