<?php

namespace App\Policies;

use App\Models\Reimbursement;
use App\Models\User;

class ReimbursementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reimbursement $reimbursement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Reimbursement $reimbursement): bool
    {
        return true;
    }

    public function delete(User $user, Reimbursement $reimbursement): bool
    {
        return true;
    }
}
