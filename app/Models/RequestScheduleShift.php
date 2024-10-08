<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RequestScheduleShift extends Pivot
{
    protected $table = 'request_schedule_shifts';
    public $timestamps = false;
    protected $fillable = ['request_schedule_id', 'shift_id', 'order'];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
