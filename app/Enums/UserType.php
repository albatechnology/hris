<?php

namespace App\Enums;

enum UserType: string
{
    use BaseEnum;

    case SUPER_ADMIN = 'super_admin';
    case ADMINISTRATOR = 'administrator';
    case ADMIN = 'admin';

    case MANAGER = 'manager'; // for SYNTEGRA only, can not be approver
    case SUPERVISOR = 'supervisor'; // for SYNTEGRA only, can be approver

    case USER = 'user';

    public function hasPermission(string $access, string $permission): bool
    {
        return true;
    }
}
