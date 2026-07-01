<?php

namespace App\Policies;

use App\Models\Bank;
use App\Models\User;

class BankPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bank_read');
    }

    public function view(User $user, Bank $bank): bool
    {
        return $user->can('bank_read');
    }

    public function create(User $user): bool
    {
        return $user->can('bank_create');
    }

    public function update(User $user, Bank $bank): bool
    {
        return $user->can('bank_edit');
    }

    public function delete(User $user, Bank $bank): bool
    {
        return $user->can('bank_delete');
    }

    public function restore(User $user, Bank $bank): bool
    {
        return $user->can('bank_access');
    }

    public function forceDelete(User $user, Bank $bank): bool
    {
        return $user->can('bank_delete');
    }
}
