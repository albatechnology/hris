<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('loan_read');
    }

    public function view(User $user, Loan $loan): bool
    {
        return $user->can('loan_read');
    }

    public function create(User $user): bool
    {
        return $user->can('loan_create');
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->can('loan_edit');
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->can('loan_delete');
    }

    public function restore(User $user, Loan $loan): bool
    {
        return $user->can('loan_access');
    }

    public function forceDelete(User $user, Loan $loan): bool
    {
        return $user->can('loan_delete');
    }
}
