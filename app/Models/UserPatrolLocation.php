<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPatrolLocation extends BaseModel
{
    protected $fillable = [
        'user_id',
        'patrol_location_id',
        'lat',
        'lng',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patrolLocation(): BelongsTo
    {
        return $this->belongsTo(PatrolLocation::class);
    }
}
