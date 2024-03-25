<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'schedule_id',
        'shift_id',
        'date',
        'is_after_shift',
        'duration',
        'note',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'is_after_shift' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected $appends = ['duration_text'];
    public function getDurationTextAttribute()
    {
        list($hours, $minutes, $seconds) = explode(':', $this->duration);

        $result = '';
        if ((int)$hours > 0) {
            $result .= (int)$hours . 'h ';
        }
        if ((int)$minutes > 0) {
            $result .= (int)$minutes . 'm ';
        }
        if ((int)$seconds > 0) {
            $result .= (int)$seconds . 's';
        }

        return trim($result);
    }

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
