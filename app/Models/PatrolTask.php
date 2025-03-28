<?php

namespace App\Models;

use App\Enums\PatrolTaskStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrolTask extends BaseModel
{
    // currently, 'status' columns is not used

    protected $fillable = [
        'patrol_location_id',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => PatrolTaskStatus::class,
    ];

    // protected $appends = ['user_patrol_task'];

    // public function getUserPatrolTaskAttribute()
    // {
    //     // Get current schedule
    //     $schedule = ScheduleService::getTodaySchedule(scheduleType: ScheduleType::PATROL->value);

    //     if ($schedule?->shift) {
    //         // Define start and end times based on shift clock_in and clock_out
    //         $start = Carbon::createFromFormat('H:i:s', $schedule->shift->clock_in);
    //         $end = Carbon::createFromFormat('H:i:s', $schedule->shift->clock_out);

    //         $currentTime = Carbon::now(); // Current time
    //         $currentPeriod = null;

    //         // Generate 2-hour intervals within the shift time
    //         while ($start->lt($end)) {
    //             $nextPeriod = $start->copy()->addMinutes(5);

    //             // Check if the current time falls within this period
    //             if ($currentTime->between($start, $nextPeriod)) {
    //                 $currentPeriod = [$start, $nextPeriod];
    //                 break;
    //             }

    //             // Move to the next period
    //             $start->addMinutes(5);
    //         }

    //         return $this->userPatrolTasks()
    //             ->where('schedule_id', $schedule->id)
    //             ->where('shift_id', $schedule->shift->id)
    //             ->whereBetween('created_at', [$currentPeriod[0]->toDateTimeString(), $currentPeriod[1]->toDateTimeString()])
    //             // ->whereHas('patrolTask', fn($q) => $q->whereNotIn('status', [PatrolTaskStatus::CANCEL->value]))
    //             ->orderBy('id', 'DESC')
    //             ->first();
    //     }

    //     return null;
    // }

    public function patrolLocation(): BelongsTo
    {
        return $this->belongsTo(PatrolLocation::class);
    }

    public function userPatrolTasks(): HasMany
    {
        return $this->hasMany(UserPatrolTask::class)->with(['user', 'media']);
    }
}
