<?php

namespace App\Services;

use App\Classes\Menu;
use App\Classes\Submenu;

class MenuService
{
    public static function menu(): array
    {
        return [
            self::dashboard(),
            self::users(),
            self::groups(),
            self::nationalholidays(),
            self::attendances(),
            self::timeoffs(),
            self::overtimes(),
        ];
    }

    protected static function dashboard()
    {
        $dashboard = new Submenu('dashboard_access', '/', 'fas fa-', 'Dashboard');

        return new Menu('dashboard_management_access', 'fas fa-tachometer-alt', 'Dashboard Management', ...[$dashboard]);
    }

    protected static function users()
    {
        $users = new Submenu('user_access', 'users', 'fas fa-', 'Users');
        $roles = new Submenu('role_access', 'roles', 'fas fa-', 'Roles');

        return new Menu('user_management_access', 'fas fa-users', 'Users', ...[$users, $roles]);
    }

    protected static function groups()
    {
        $groups = new Submenu('group_access', 'groups', 'fas fa-', 'Groups');
        $companies = new Submenu('role_access', 'companies', 'fas fa-', 'Companies');
        $branches = new Submenu('role_access', 'branches', 'fas fa-', 'Branches');

        return new Menu('group_management_access', 'fas fa-building', 'Groups', ...[$groups, $companies, $branches]);
    }

    protected static function nationalholidays()
    {
        $nationalholidays = new Submenu('national_holiday_access', 'national-holidays', 'fas fa-', 'National-holidays');

        return new Menu('national_holiday_management_access', 'fas fa-building', 'National-holidays', ...[$nationalholidays]);
    }

    protected static function attendances()
    {
        $attendances = new Submenu('attendance_access', 'attendances', 'fas fa-', 'Attendances');

        return new Menu('attendance_management_access', 'fas fa-building', 'Attendances', ...[$attendances]);
    }

    protected static function timeoffs()
    {
        $timeoffs = new Submenu('timeoff_access', 'timeoffs', 'fas fa-', 'Timeoffs');

        return new Menu('timeoff_management_access', 'fas fa-building', 'Timeoffs', ...[$timeoffs]);
    }

    protected static function overtimes()
    {
        $overtimes = new Submenu('overtime_access', 'overtimes', 'fas fa-', 'Overtimes');

        return new Menu('overtime_management_access', 'fas fa-building', 'Overtimes', ...[$overtimes]);
    }
}
