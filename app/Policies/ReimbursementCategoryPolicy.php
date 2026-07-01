<?php

namespace App\Policies;

use App\Models\ReimbursementCategory;
use App\Models\User;

class ReimbursementCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
        // return $user->can('reimbursement_category_read');
    }

    public function view(User $user, ReimbursementCategory $reimbursementCategory): bool
    {
        return true;
        // return $user->can('reimbursement_category_read');
    }

    public function create(User $user): bool
    {
        return $user->can('reimbursement_category_create');
    }

    public function update(User $user, ReimbursementCategory $reimbursementCategory): bool
    {
        return $user->can('reimbursement_category_edit');
    }

    public function delete(User $user, ReimbursementCategory $reimbursementCategory): bool
    {
        return $user->can('reimbursement_category_delete');
    }

    public function restore(User $user, ReimbursementCategory $reimbursementCategory): bool
    {
        return $user->can('reimbursement_category_access');
    }

    public function forceDelete(User $user, ReimbursementCategory $reimbursementCategory): bool
    {
        return $user->can('reimbursement_category_delete');
    }
}
