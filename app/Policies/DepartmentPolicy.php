<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('department_read');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('department_read');
    }

    public function create(User $user): bool
    {
        return $user->can('department_create');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('department_edit');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can('department_delete');
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return $user->can('department_delete');
    }

    public function restore(User $user, Department $department): bool
    {
        return $user->can('department_access');
    }
}