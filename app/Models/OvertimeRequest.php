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
        'overtime_id',
        'start_at',
        'end_at',
        'note',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_at' => 'timestamp',
        'end_at' => 'timestamp',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

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
