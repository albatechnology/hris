<?php

namespace App\Policies;

use App\Models\BranchLocation;
use App\Models\User;

class BranchLocationPolicy
{
    // use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('branch_read');
    }

    public function view(User $user, BranchLocation $branchLocation): bool
    {
        return $user->can('branch_read');
    }

    public function create(User $user): bool
    {
        return $user->can('branch_create');
    }

    public function update(User $user, BranchLocation $branchLocation): bool
    {
        return $user->can('branch_edit');
    }

    public function delete(User $user, BranchLocation $branchLocation): bool
    {
        return $user->can('branch_delete');
    }

    public function restore(User $user, BranchLocation $branchLocation): bool
    {
        return $user->can('branch_access');
    }

    public function forceDelete(User $user, BranchLocation $branchLocation): bool
    {
        return $user->can('branch_delete');
    }
}