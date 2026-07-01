<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    // use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('announcement_read');
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->can('announcement_read');
    }

    public function create(User $user): bool
    {
        return $user->can('announcement_create');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->can('announcement_edit');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->can('announcement_delete');
    }

    public function restore(User $user, Announcement $announcement): bool
    {
        return $user->can('announcement_access');
    }

    public function forceDelete(User $user, Announcement $announcement): bool
    {
        return $user->can('announcement_delete');
    }
}