<?php

namespace App\Models;

use App\Enums\OvertimeStatus;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'date',
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
        // 'user_id' => 'integer',
        // 'date' => 'date',
        // 'shift_id' => 'integer',
        // 'overtime_id' => 'integer',
        'start_at' => 'datetime:H:i',
        'end_at' => 'datetime:H:i',
        'is_approved' => 'boolean',
        // 'status' => OvertimeStatus::class,
        // 'approved_by' => 'integer',
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
