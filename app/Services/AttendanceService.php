<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use DateTime;

class AttendanceService
{
    public static function getTodayAttendance(int|string $scheduleId, int|string $shiftId, ?User $user = null, $date = null): ?Attendance
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        $date = is_null($date) ? date('Y-m-d') : date('Y-m-d', strtotime($date));

        $attendance = Attendance::where('schedule_id', $scheduleId)
            ->where('shift_id', $shiftId)
            ->whereHas('details', fn ($q) => $q->whereDate('time', $date))
            ->first();

        if (!$attendance) {
            return null;
        }

        return $attendance;
    }

    public static function getSumOvertimeDuration(User|int $user, $date)
    {
        if ($user instanceof User) {
            $user = $user->id;
        }

        $overtimeRequests = \App\Models\OvertimeRequest::tenanted()
            // ->where('is_approved', true)
            ->where('user_id', $user)
            ->where('date', $date)
            ->get(['duration']);

        if ($overtimeRequests->count() <= 0) return null;

        $totalSeconds = 0;
        foreach ($overtimeRequests as $overtimeRequest) {
            list($hours, $minutes, $seconds) = explode(':', $overtimeRequest->duration);
            $totalSeconds += $hours * 3600 + $minutes * 60 + $seconds;
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        $result = '';
        if ((int)$hours > 0) {
            $result .= (int)$hours . 'h ';
        }
        if ((int)$minutes > 0) {
            $result .= (int)$minutes . 'm ';
        }
        if ((int)$seconds > 0) {
            $result .= (int)$seconds . 's';
        }

        return trim($result);
    }
}
