<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shift_read');
    }

    public function view(User $user, Shift $shift): bool
    {
        return $user->can('shift_read');
    }

    public function create(User $user): bool
    {
        return $user->can('shift_create');
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->can('shift_edit');
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $user->can('shift_delete');
    }

    public function forceDelete(User $user, Shift $shift): bool
    {
        return $user->can('shift_delete');
    }

    public function restore(User $user, Shift $shift): bool
    {
        return $user->can('shift_access');
    }

     public function importShiftUsers(User $user): bool
    {
        return $user->can('shift_import');
    }
}