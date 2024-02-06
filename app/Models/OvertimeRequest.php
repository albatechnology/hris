<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'overtime_id',
        'user_id',
        'shift_id',
        'date',
        'start_at',
        'end_at',
        'note',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'overtime_id' => 'integer',
        'user_id' => 'integer',
        'shift_id' => 'integer',
        'date' => 'date',
        'start_at' => 'time',
        'end_at' => 'time',
        'note' => 'text',
        'is_approved' => 'boolean',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
