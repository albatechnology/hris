<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPatrol extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'patrol_id',
        'start_time',
        'end_time',
    ];

    public function patrol(): BelongsTo
    {
        return $this->belongsTo(Patrol::class);
    }
}
