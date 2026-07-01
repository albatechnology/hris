<?php

namespace App\Policies;

use App\Models\CustomField;
use App\Models\User;

class CustomFieldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('custom_field_read');
    }

    public function view(User $user, CustomField $customField): bool
    {
        return $user->can('custom_field_read');
    }

    public function create(User $user): bool
    {
        return $user->can('custom_field_create');
    }

    public function update(User $user, CustomField $customField): bool
    {
        return $user->can('custom_field_edit');
    }

    public function delete(User $user, CustomField $customField): bool
    {
        return $user->can('custom_field_delete');
    }

    public function forceDelete(User $user, CustomField $customField): bool
    {
        return $user->can('custom_field_delete');
    }

    public function restore(User $user, CustomField $customField): bool
    {
        return $user->can('custom_field_access');
    }
}