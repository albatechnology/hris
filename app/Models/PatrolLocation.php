<?php

namespace App\Models;

use App\Enums\PatrolTaskStatus;
use App\Enums\ScheduleType;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrolLocation extends BaseModel
{
    protected $fillable = [
        'patrol_id',
        'client_location_id',
        'description',
    ];

    protected $appends = ['attended_at', 'status', 'total_task'];

    public function getAttendedAtAttribute()
    {
        return $this->userPatrolLocations()->where('user_id', auth('sanctum')->id())->first()?->created_at ?? null;
    }

    public function getStatusAttribute()
    {
        // if(!$this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && !$this->tasks()->where('status', PatrolTaskStatus::COMPLETE && !$this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first())->first()){
        //     return null;
        // }
        // if($this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && ($this->tasks()->where('status', PatrolTaskStatus::COMPLETE || $this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first()))->first()){
        //     return 'progress';
        // }
        // if(!$this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && $this->tasks()->where('status', PatrolTaskStatus::COMPLETE && !$this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first())->first()){
        //     return 'complete';
        // }
        // if(!$this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && !$this->tasks()->where('status', PatrolTaskStatus::COMPLETE && $this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first())->first()){
        //     return 'cancel';
        // }

        // Get current schedule
        $schedule = ScheduleService::getTodaySchedule(scheduleType: ScheduleType::PATROL->value);

        if ($schedule?->shift) {
            // Define start and end times based on shift clock_in and clock_out
            $start = Carbon::createFromFormat('H:i:s', $schedule->shift->clock_in);
            $end = Carbon::createFromFormat('H:i:s', $schedule->shift->clock_out);

            $currentTime = Carbon::now(); // Current time
            $currentPeriod = null;

            // Generate 2-hour intervals within the shift time
            while ($start->lt($end)) {
                $nextPeriod = $start->copy()->addMinutes(5);

                // Check if the current time falls within this period
                if ($currentTime->between($start, $nextPeriod)) {
                    $currentPeriod = [$start, $nextPeriod];
                    break;
                }

                // Move to the next period
                $start->addMinutes(5);
            }

            return $this->tasks()->whereHas('userPatrolTasks', function ($q) use ($schedule, $currentPeriod) {
                $q->where('schedule_id', $schedule->id);
                $q->where('shift_id', $schedule->shift->id);
                $q->whereBetween('created_at', [$currentPeriod[0]->toDateTimeString(), $currentPeriod[1]->toDateTimeString()]);
                // ->whereHas('patrolTask', fn($q) => $q->whereNotIn('status', [PatrolTaskStatus::CANCEL->value]))
                $q->orderBy('id', 'DESC');
            })->first() ? 'complete' : null;
        }

        return null;
    }

    public function getTotalTaskAttribute()
    {
        return $this->tasks()->count();
    }

    public function patrol(): BelongsTo
    {
        return $this->belongsTo(Patrol::class);
    }

    public function clientLocation(): BelongsTo
    {
        return $this->belongsTo(ClientLocation::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(PatrolTask::class);
    }

    public function userPatrolLocations(): HasMany
    {
        return $this->hasMany(UserPatrolLocation::class);
    }
}
