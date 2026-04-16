<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('supervisor_request_schedule_read');
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return $user->can('supervisor_request_schedule_read');
    }

    public function create(User $user): bool
    {
        return $user->can('supervisor_request_schedule_create');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->can('supervisor_request_schedule_edit');
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->can('supervisor_request_schedule_delete');
    }

    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return $user->can('supervisor_request_schedule_delete');
    }

    public function restore(User $user, Schedule $schedule): bool
    {
        return $user->can('supervisor_request_schedule_access');
    }

    public function approve(User $user, Schedule $schedule): bool
    {
        return $user->can('schedule_edit');
    }
}