<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('group_read');
    }

    public function view(User $user, Group $group): bool
    {
        return $user->can('group_read');
    }

    public function create(User $user): bool
    {
        return $user->can('group_create');
    }

    public function update(User $user, Group $group): bool
    {
        return $user->can('group_edit');
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->can('group_delete');
    }

    public function forceDelete(User $user, Group $group): bool
    {
        return $user->can('group_delete');
    }

    public function restore(User $user, Group $group): bool
    {
        return $user->can('group_access');
    }
}
