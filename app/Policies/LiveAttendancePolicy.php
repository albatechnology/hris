<?php

namespace App\Policies;

use App\Models\LiveAttendance;
use App\Models\User;

class LiveAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('live_attendance_read');
    }

    public function view(User $user, LiveAttendance $liveAttendance): bool
    {
        return $user->can('live_attendance_read');
    }

    public function create(User $user): bool
    {
        return $user->can('live_attendance_create');
    }

    public function update(User $user, LiveAttendance $liveAttendance): bool
    {
        return $user->can('live_attendance_edit');
    }

    public function delete(User $user, LiveAttendance $liveAttendance): bool
    {
        return $user->can('live_attendance_delete');
    }

    public function forceDelete(User $user, LiveAttendance $liveAttendance): bool
    {
        return $user->can('live_attendance_delete');
    }

    public function restore(User $user, LiveAttendance $liveAttendance): bool
    {
        return $user->can('live_attendance_access');
    }
}
