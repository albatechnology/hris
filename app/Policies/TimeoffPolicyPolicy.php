<?php

namespace App\Policies;

use App\Models\TimeoffPolicy;
use App\Models\User;

class TimeoffPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
        // return $user->can('timeoff_policy_read');
    }

    public function view(User $user, TimeoffPolicy $timeoffPolicy): bool
    {
        return true;
        // return $user->can('timeoff_policy_read');
    }

    public function create(User $user): bool
    {
        return $user->can('timeoff_policy_create');
    }

    public function update(User $user, TimeoffPolicy $timeoffPolicy): bool
    {
        return $user->can('timeoff_policy_edit');
    }

    public function delete(User $user, TimeoffPolicy $timeoffPolicy): bool
    {
        return $user->can('timeoff_policy_delete');
    }

    public function forceDelete(User $user, TimeoffPolicy $timeoffPolicy): bool
    {
        return $user->can('timeoff_policy_delete');
    }

    public function restore(User $user, TimeoffPolicy $timeoffPolicy): bool
    {
        return $user->can('timeoff_policy_access');
    }
}
