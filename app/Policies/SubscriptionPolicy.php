<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
        // return $user->can('subscription_read');
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return true;
        // return $user->can('subscription_read');
    }

    public function create(User $user): bool
    {
        return true;
        // return $user->can('subscription_create');
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return true;
        // return $user->can('subscription_edit');
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return true;
        // return $user->can('subscription_delete');
    }

    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return true;
        // return $user->can('subscription_delete');
    }

    public function restore(User $user, Subscription $subscription): bool
    {
        return true;
        // return $user->can('subscription_access');
    }
}