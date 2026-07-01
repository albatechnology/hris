<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('company_read');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can('company_read');
    }

    public function create(User $user): bool
    {
        return $user->can('company_create');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can('company_edit');
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can('company_delete');
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->can('company_access');
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->can('company_delete');
    }
}
