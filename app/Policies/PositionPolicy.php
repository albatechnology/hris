<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('position_read');
    }

    public function view(User $user, Position $position): bool
    {
        return $user->can('position_read');
    }

    public function create(User $user): bool
    {
        return $user->can('position_create');
    }

    public function update(User $user, Position $position): bool
    {
        return $user->can('position_edit');
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->can('position_delete');
    }

    public function restore(User $user, Position $position): bool
    {
        return $user->can('position_access');
    }

    public function forceDelete(User $user, Position $position): bool
    {
        return $user->can('position_delete');
    }
}
