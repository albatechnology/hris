<?php

namespace App\Models;

use App\Enums\PatrolTaskStatus;
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
        if(!$this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && !$this->tasks()->where('status', PatrolTaskStatus::COMPLETE && !$this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first())->first()){
            return null;
        }

        if($this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && ($this->tasks()->where('status', PatrolTaskStatus::COMPLETE || $this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first()))->first()){
            return 'progress';
        }

        if(!$this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && $this->tasks()->where('status', PatrolTaskStatus::COMPLETE && !$this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first())->first()){
            return 'complete';
        }

        if(!$this->tasks()->where('status', PatrolTaskStatus::PENDING)->first() && !$this->tasks()->where('status', PatrolTaskStatus::COMPLETE && $this->tasks()->where('status', PatrolTaskStatus::CANCEL)->first())->first()){
            return 'cancel';
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
