<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

// sekarang tidak dipake dulu, sebagai gantinya pake schedule shift attendance
class UserPatrolSchedule extends BaseModel
{
    protected $fillable = [
        'user_patrol_id',
        'schedule_id',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
