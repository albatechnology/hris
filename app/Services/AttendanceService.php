<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;

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

    // public static function getSumOvertimeDuration(User|int $user, $date, OvertimeRequestType $requestType = null)
    public static function getSumOvertimeDuration(User|int $user, $startDate, $endDate = null, bool $formatText = true, callable $query = null)
    {
        if ($user instanceof User) {
            $user = $user->id;
        }

        $overtimeRequests = \App\Models\OvertimeRequest::tenanted()
            ->where('approval_status', \App\Enums\ApprovalStatus::APPROVED)
            ->where('user_id', $user)
            ->when(
                is_null($endDate),
                fn ($q) => $q->whereDate('date', $startDate),
                fn ($q) => $q->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate)
            )
            ->when($query, $query)
            ->get(['duration']);

        if ($overtimeRequests->count() <= 0) return null;

        $totalSeconds = 0;
        foreach ($overtimeRequests as $overtimeRequest) {
            // $startAt = new \DateTime($overtimeRequest->start_at);
            // $endAt = new \DateTime($overtimeRequest->end_at);
            // $interval = $startAt->diff($endAt);

            // $totalSeconds += ((int)$interval->format('%d') * 3600 * 24) + ((int)$interval->format('%h') * 3600) + ((int)$interval->format('%s') * 60) + (int)$interval->format('%s');

            list($hours, $minutes, $seconds) = explode(':', $overtimeRequest->duration);
            $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        // $seconds = $totalSeconds % 60;

        $result = '';
        if ($formatText) {
            if ((int)$hours > 0) {
                $result .= (int)$hours . 'h ';
            }
            if ((int)$minutes > 0) {
                $result .= (int)$minutes . 'm ';
            }
            // if ((int)$seconds > 0) {
            //     $result .= (int)$seconds . 's';
            // }
        } else {
            $result = sprintf("%02d:%02d:00", $hours, $minutes);
            // $result = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        }

        return trim($result);
    }
}
