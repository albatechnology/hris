<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveAttendanceLocation extends BaseModel
{
    protected $fillable = [
        'live_attendance_id',
        'name',
        'radius',
        'lat',
        'lng',
    ];

    public function liveAttendance(): BelongsTo
    {
        return $this->belongsTo(LiveAttendance::class);
    }
}
