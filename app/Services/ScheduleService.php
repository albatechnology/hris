<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;

class ScheduleService
{
    /**
     * get user today schedule.
     */
    public static function getTodaySchedule(?User $user = null, $date = null): ?Schedule
    {
        if (! $user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        $date = is_null($date) ? date('Y-m-d') : date('Y-m-d', strtotime($date));

        $schedule = $user->schedules()->whereDate('effective_date', '<=', $date)->orderByDesc('effective_date')->first();
        if (! $schedule) {
            return null;
        }

        return $schedule->load('shift');
    }

    /**
     * Check the availability of user's schedule within a given date range.
     *
     * @param  User|null  $user  The user for whom the schedule availability is being checked.
     * @param  mixed  $startDate  The effective_date for the schedule availability check.
     * @param  mixed  $endDate  The effective_date for the schedule availability check.
     */
    public static function checkAvailableSchedule(?User $user = null, $startDate = null, $endDate = null): bool
    {
        if (! $user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        $startDate = is_null($startDate) ? date('Y-m-d') : date('Y-m-d', strtotime($startDate));
        $endDate = is_null($endDate) ? $startDate : date('Y-m-d', strtotime($endDate));

        return $user->schedules()->whereDate('effective_date', '<=', $startDate)->orWhereDate('effective_date', '<=', $endDate)->orderByDesc('effective_date')->exists();
    }
}
