<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class ShiftService
{
    public static function getIntervalHours(Shift $shift): int
    {
        $shiftClockIn = $shift->clock_in;
        $shiftClockOut = $shift->clock_out;

        if (strtotime($shiftClockOut) < strtotime($shiftClockIn)) {
            $shiftClockIn = new Carbon(date('Y-m-d ' . $shift->clock_in));
            $shiftClockOut = new Carbon(date('Y-m-d ' . $shift->clock_out, strtotime('+1 day')));
        } else {
            $shiftClockIn = new Carbon(date('Y-m-d ' . $shift->clock_in));
            $shiftClockOut = new Carbon(date('Y-m-d ' . $shift->clock_out));
        }

        $interval = max($shiftClockIn->diffInHours($shiftClockOut), 0);
        return ceil($interval);
    }

    public static function validateRequestShift(Shift|int $shiftId, User $user = null): bool
    {
        if ($shiftId instanceof Shift) {
            $shiftId = $shiftId->id;
        }

        if (!$user) {
            /** @var User $user */
            $user = auth()->user();
        }

        // $user->load('positions');

        $branchId = $user->branch_id;
        $jobPositionIds = $user->job_position_id ? [$user->job_position_id] : [];
        $jobLevelIds = $user->job_level_id ? [$user->job_level_id] : [];
        // $departmentIds = $user->positions->pluck('job_level_id')?->toArray();
        // $positionIds = $user->positions->pluck('position_id')?->toArray();

        $schedule = ScheduleService::getTodaySchedule(scheduleColumn: ['id'], shiftColumn: ['id']);
        return Shift::where(
            fn($q) =>
            $q->tenanted()
                ->where(
                    fn($q) => $q->when($schedule, fn($q) => $q->whereHas('schedules', fn($q) => $q->where('schedule_id', $schedule->id)))
                        ->orWhere(function ($q) use ($branchId, $jobLevelIds, $jobPositionIds) {
                            $q->where('is_show_in_request', true)->where('is_show_in_request_for_all', true)
                                ->orWhere(function ($q) use ($branchId) {
                                    $q->where('is_show_in_request', true)->where('is_show_in_request_for_all', false)->whereRaw('JSON_CONTAINS(show_in_request_branch_ids, ?)', json_encode($branchId));
                                })
                                ->orWhere(function ($q) use ($jobLevelIds) {
                                    $q->where('is_show_in_request', true)->where('is_show_in_request_for_all', false)->whereRaw('JSON_CONTAINS(show_in_request_job_level_ids, ?)', json_encode($jobLevelIds));
                                })
                                ->orWhere(function ($q) use ($jobPositionIds) {
                                    $q->where('is_show_in_request', true)->where('is_show_in_request_for_all', false)->whereRaw('JSON_CONTAINS(show_in_request_job_position_ids, ?)', json_encode($jobPositionIds));
                                });
                        })
                )
                ->orWhereNull('company_id')
        )
            ->where('id', $shiftId)
            ->exists();
    }
}
