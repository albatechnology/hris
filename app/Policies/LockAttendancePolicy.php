<?php

namespace App\Policies;

use App\Models\LockAttendance;
use App\Models\User;

class LockAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lock_attendance_read');
    }

    public function view(User $user, LockAttendance $lockAttendance): bool
    {
        return $user->can('lock_attendance_read');
    }

    public function create(User $user): bool
    {
        return $user->can('lock_attendance_create');
    }

    public function update(User $user, LockAttendance $lockAttendance): bool
    {
        return $user->can('lock_attendance_edit');
    }

    public function delete(User $user, LockAttendance $lockAttendance): bool
    {
        return $user->can('lock_attendance_delete');
    }

    public function restore(User $user, LockAttendance $lockAttendance): bool
    {
        return $user->can('lock_attendance_access');
    }

    public function forceDelete(User $user, LockAttendance $lockAttendance): bool
    {
        return $user->can('lock_attendance_delete');
    }
}
