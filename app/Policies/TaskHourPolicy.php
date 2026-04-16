<?php

namespace App\Policies;

use App\Models\TaskHour;
use App\Models\User;

class TaskHourPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('task_read');
    }

    public function view(User $user, TaskHour $taskHour): bool
    {
        return $user->can('task_read');
    }

    public function create(User $user): bool
    {
        return $user->can('task_create');
    }

    public function update(User $user, TaskHour $taskHour): bool
    {
        return $user->can('task_edit');
    }

    public function delete(User $user, TaskHour $taskHour): bool
    {
        return $user->can('task_delete');
    }

    public function forceDelete(User $user, TaskHour $taskHour): bool
    {
        return $user->can('task_delete');
    }

    public function restore(User $user, TaskHour $taskHour): bool
    {
        return $user->can('task_access');
    }
}
