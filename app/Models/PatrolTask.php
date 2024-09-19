<?php

namespace App\Models;

use App\Enums\PatrolTaskStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrolTask extends BaseModel
{
    protected $fillable = [
        'patrol_location_id',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => PatrolTaskStatus::class,
    ];

    public function patrolLocation(): BelongsTo
    {
        return $this->belongsTo(PatrolLocation::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserPatrolTask::class);
    }
}
