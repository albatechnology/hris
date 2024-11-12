<?php

namespace App\Services;

use App\Models\User;

class TaskService
{
    public static function getSumDuration(User|int $user, $date)
    {
        if ($user instanceof User) {
            $user = $user->id;
        }

        $taskRequests = \App\Models\TaskRequest::tenanted()
            ->approved()
            // ->where('approval_status', \App\Enums\ApprovalStatus::APPROVED)
            ->where('user_id', $user)
            ->whereDate('start_at', $date)
            ->get(['start_at', 'end_at']);

        if ($taskRequests->count() <= 0) return null;

        $totalSeconds = 0;
        foreach ($taskRequests as $taskRequest) {
            $startAt = new \DateTime($taskRequest->start_at);
            $endAt = new \DateTime($taskRequest->end_at);
            $interval = $startAt->diff($endAt);

            $totalSeconds += ((int)$interval->format('%d') * 3600 * 24) + ((int)$interval->format('%h') * 3600) + ((int)$interval->format('%s') * 60) + (int)$interval->format('%s');
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        $result = '';
        if ((int)$hours > 0) {
            $result .= (int)$hours . 'h ';
        }
        if ((int)$minutes > 0) {
            $result .= (int)$minutes . 'm ';
        }
        // if ((int)$seconds > 0) {
        //     $result .= (int)$seconds . 's';
        // }

        return trim($result);
    }
}
