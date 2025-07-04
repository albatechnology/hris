<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Services\ScheduleService;
use App\Traits\Models\BelongsToBranch;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patrol extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes, BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'name',
        'start_date',
        'end_date',
        'lat',
        'lng',
        'description',
    ];

    // protected $appends = ['total_task', 'completed_task', 'status'];

    // public function getTotalTaskAttribute()
    // {
    //     return $this->tasks()->count();
    // }

    // public function getCompletedTaskAttribute()
    // {
    //     return $this->tasks()->where('status', PatrolTaskStatus::COMPLETE)->count();
    // }

    // public function getStatusAttribute()
    // {
    //     // if (!$this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && !$this->tasks()->where('status', PatrolTaskStatus::COMPLETE && !$this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first())->first()) {
    //     //     return null;
    //     // }
    //     // if ($this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && ($this->tasks()->where('status', PatrolTaskStatus::COMPLETE || $this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first()))->first()) {
    //     //     return 'progress';
    //     // }
    //     // if (!$this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && $this->tasks()->where('status', PatrolTaskStatus::COMPLETE && !$this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first())->first()) {
    //     //     return 'complete';
    //     // }
    //     // if (!$this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && !$this->tasks()->where('status', PatrolTaskStatus::COMPLETE && $this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first())->first()) {
    //     //     return 'cancel';
    //     // }$schedule = ScheduleService::getTodaySchedule(scheduleType: ScheduleType::PATROL->value);

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

    //         return $this->tasks()->whereDoesntHave('userPatrolTasks', function ($q) use ($schedule, $currentPeriod) {
    //             $q->where('schedule_id', $schedule->id);
    //             $q->where('shift_id', $schedule->shift->id);
    //             $q->whereBetween('created_at', [$currentPeriod[0]->toDateTimeString(), $currentPeriod[1]->toDateTimeString()]);
    //             // ->whereHas('patrolTask', fn($q) => $q->whereNotIn('status', [PatrolTaskStatus::CANCEL->value]))
    //             $q->orderBy('id', 'DESC');
    //         })->first() ? null : 'complete';
    //     }

    //     return null;
    // }

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        // if ($user->is_super_admin) {
        //     return $query;
        // }

        // if ($user->is_administrator) {
        //     return $query->whereHas('branch', fn($q) => $q->whereHas('company', fn($q) => $q->where('group_id', $user->group_id)));
        // }

        // if ($user->is_admin) {
        //     $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
        //     return $query->whereHas('branch', fn($q) => $q->whereIn('company_id', $companyIds));
        // }

        if (!$user->is_user) {
            return $query->whereHas('branch', fn($q) => $q->tenanted());
        }
        // return $query;
        // $schedule = ScheduleService::getTodaySchedule(user: $user, scheduleType: ScheduleType::PATROL->value);
        $schedule = ScheduleService::getTodaySchedule(user: $user, scheduleColumn: ['id'], shiftColumn: ['id', 'is_dayoff']);

        if (!$schedule || $schedule->shift?->is_dayoff) {
            // return no data
            return $query->where('id', '<', 0);
        }

        return $query->whereHas(
            'users',
            fn($q) => $q->where('user_id', $user->id)
            // ->whereHas('userPatrolSchedules.schedule', function ($q2) {
            //     $q2->where('schedules.type', ScheduleType::PATROL->value);
            //     $q2->whereDate('schedules.effective_date', '<=', date('Y-m-d'));
            //     // $q2->whereHas('shifts', fn($q3) => $q3->where('id', $schedule?->shift?->id));
            // })
        );

        // return $query->whereHas(
        //     'users',
        //     fn($q) => $q->where('user_id', $user->id)
        //         ->whereHas('userPatrolSchedules.schedule', function ($q2) {
        //             $q2->where('schedules.type', ScheduleType::PATROL->value);
        //             $q2->whereDate('schedules.effective_date', '<=', date('Y-m-d'));
        //             // $q2->whereHas('shifts', fn($q3) => $q3->where('id', $schedule?->shift?->id));
        //         })
        // );
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function scopeSelectMinimalist(Builder $query, array $additionalColumns = [])
    {
        $query->select(['patrols.id', 'patrols.branch_id', 'patrols.name', 'patrols.start_date', 'patrols.end_date', 'patrols.lat', 'patrols.lng', 'patrols.description', ...$additionalColumns]);
    }

    public function tasks()
    {
        return $this->hasManyThrough(PatrolTask::class, PatrolLocation::class);
    }

    public function patrolLocations(): HasMany
    {
        return $this->hasMany(PatrolLocation::class);
    }

    public function patrolHours(): HasMany
    {
        return $this->hasMany(PatrolHour::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserPatrol::class);
    }

    public function userPatrolBatches(): HasMany
    {
        return $this->hasMany(UserPatrolBatch::class);
    }
}
