<?php

namespace App\Observers;

use App\Enums\UserType;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "saving" event.
     */
    public function saving(User $user): void
    {
        if (!empty($user->branch_id)) {
            $user->company_id = $user->branch?->company_id;
            $user->group_id = $user->branch?->company?->group_id;
        }
    }

    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        if (empty($user->type)) {
            $user->type = UserType::USER;
        }

        if (empty($user->join_date)) {
            $user->join_date = '2024-01-01';
        }

        if (empty($user->sign_date)) {
            $user->sign_date = $user->join_date;
        }

        if ($user->company_id) {
            $user->overtime_id = \App\Models\Overtime::where('company_id', $user->company_id)->first(['id'])?->id ?? null;
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void {}

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void {}

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
