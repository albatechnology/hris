<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('incident_read');
    }

    public function view(User $user, Incident $incident): bool
    {
        return $user->can('incident_read');
    }

    public function create(User $user): bool
    {
        return $user->can('incident_create');
    }

    public function update(User $user, Incident $incident): bool
    {
        return $user->can('incident_edit');
    }

    public function delete(User $user, Incident $incident): bool
    {
        return $user->can('incident_delete');
    }

    public function forceDelete(User $user, Incident $incident): bool
    {
        return $user->can('incident_delete');
    }

    public function restore(User $user, Incident $incident): bool
    {
        return $user->can('incident_access');
    }
}
