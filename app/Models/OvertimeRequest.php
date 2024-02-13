<?php

namespace App\Models;

use App\Traits\BelongsToUser;
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
        'user_id' => 'integer',
        'date' => 'date',
        'shift_id' => 'integer',
        'overtime_id' => 'integer',
        'start_at' => 'datetime:H:i',
        'end_at' => 'datetime:H:i',
        'note' => 'string',
        'is_approved' => 'boolean',
        'approved_by' => 'integer',
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
}
