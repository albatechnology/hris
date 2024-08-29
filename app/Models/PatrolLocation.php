<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrolLocation extends BaseModel
{
    protected $fillable = [
        'patrol_id',
        'client_location_id'
    ];

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
}
