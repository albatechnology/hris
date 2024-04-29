<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use DateTime;

class ScheduleService
{
    /**
     * get user today schedule.
     */
    public static function getTodaySchedule(?User $user = null, $date = null, array $scheduleColumn = [], array $shiftColumn = [])
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        $date = is_null($date) ? date('Y-m-d') : date('Y-m-d', strtotime($date));

        /** @var Schedule $schedule */
        $schedule = $user->schedules()
            ->select(count($scheduleColumn) > 0 ? $scheduleColumn : ['*'])
            ->whereDate('effective_date', '<=', $date)->orderByDesc('effective_date')->first();

        if (!$schedule) {
            return null;
        }

        $totalShifts = $schedule->shifts()->count();
        $startDate = new DateTime($schedule->effective_date);
        $endDate = new DateTime($date);
        $interval = $startDate->diff($endDate)->days + 1;
        $order = $interval % $schedule->shifts()->count();
        $order = $order > 0 ? $order : $totalShifts;

        unset($schedule->pivot);
        return $schedule->load(['shift' => fn ($q) => $q->select(count($shiftColumn) > 0 ? $shiftColumn : ['*'])->where('order', $order)]);
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
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        $startDate = is_null($startDate) ? date('Y-m-d') : date('Y-m-d', strtotime($startDate));
        $endDate = is_null($endDate) ? $startDate : date('Y-m-d', strtotime($endDate));

        return $user->schedules()->whereDate('effective_date', '<=', $startDate)->orWhereDate('effective_date', '<=', $endDate)->orderByDesc('effective_date')->exists();
    }
}
