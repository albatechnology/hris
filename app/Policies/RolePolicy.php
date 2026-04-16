<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('role_read');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('role_read');
    }

    public function create(User $user): bool
    {
        return $user->can('role_create');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->id == 1) {
            return false;
        }
        return $user->can('role_edit');
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->id == 1) {
            return false;
        }
        return $user->can('role_delete');
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $user->can('role_delete');
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->can('role_access');
    }
}