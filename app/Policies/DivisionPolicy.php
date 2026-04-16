<?php

namespace App\Policies;

use App\Models\Division;
use App\Models\User;

class DivisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('division_read');
    }

    public function view(User $user, Division $division): bool
    {
        return $user->can('division_read');
    }

    public function create(User $user): bool
    {
        return $user->can('division_create');
    }

    public function update(User $user, Division $division): bool
    {
        return $user->can('division_edit');
    }

    public function delete(User $user, Division $division): bool
    {
        return $user->can('division_delete');
    }

    public function forceDelete(User $user, Division $division): bool
    {
        return $user->can('division_delete');
    }

    public function restore(User $user, Division $division): bool
    {
        return $user->can('division_access');
    }
}