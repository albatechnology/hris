<?php

namespace App\Policies;

use App\Models\LiveAttendanceLocation;
use App\Models\User;

class LiveAttendanceLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('live_attendance_read');
    }

    public function view(User $user, LiveAttendanceLocation $liveAttendanceLocation): bool
    {
        return $user->can('live_attendance_read');
    }

    public function create(User $user): bool
    {
        return $user->can('live_attendance_create');
    }

    public function update(User $user, LiveAttendanceLocation $liveAttendanceLocation): bool
    {
        return $user->can('live_attendance_edit');
    }

    public function delete(User $user, LiveAttendanceLocation $liveAttendanceLocation): bool
    {
        return $user->can('live_attendance_delete');
    }

    public function forceDelete(User $user, LiveAttendanceLocation $liveAttendanceLocation): bool
    {
        return $user->can('live_attendance_delete');
    }

    public function restore(User $user, LiveAttendanceLocation $liveAttendanceLocation): bool
    {
        return $user->can('live_attendance_access');
    }
}
