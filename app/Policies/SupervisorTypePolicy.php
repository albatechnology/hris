<?php

namespace App\Policies;

use App\Models\SupervisorType;
use App\Models\User;

class SupervisorTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('supervisor_type_read');
    }

    public function view(User $user, SupervisorType $supervisorType): bool
    {
        return $user->can('supervisor_type_read');
    }

    public function create(User $user): bool
    {
        return $user->can('supervisor_type_create');
    }

    public function update(User $user, SupervisorType $supervisorType): bool
    {
        return $user->can('supervisor_type_edit');
    }

    public function delete(User $user, SupervisorType $supervisorType): bool
    {
        return $user->can('supervisor_type_delete');
    }

    public function forceDelete(User $user, SupervisorType $supervisorType): bool
    {
        return $user->can('supervisor_type_delete');
    }

    public function restore(User $user, SupervisorType $supervisorType): bool
    {
        return $user->can('supervisor_type_access');
    }
}