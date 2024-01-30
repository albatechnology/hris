<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ScheduleShift extends Pivot
{
    protected $table = 'schedule_shifts';
    protected $fillable = ['schedule_id', 'shift_id', 'order'];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
