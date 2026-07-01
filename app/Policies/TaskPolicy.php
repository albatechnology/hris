<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('task_read');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->can('task_read');
    }

    public function create(User $user): bool
    {
        return $user->can('task_create');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->can('task_edit');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('task_delete');
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $user->can('task_delete');
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->can('task_access');
    }
}