<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrolLocation extends BaseModel
{
    protected $fillable = [
        'patrol_id',
        'client_location_id',
        'description',
    ];

    protected $appends = ['attended_at'];

    public function getAttendedAtAttribute()
    {
        return $this->userPatrolLocations()->where('user_id', auth('sanctum')->id())->first()?->created_at ?? null;
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
