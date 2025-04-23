<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeMultiplier extends BaseModel
{
    protected $fillable = [
        'overtime_id',
        'is_weekday',
        'start_hour',
        'end_hour',
        'multiply',
    ];

    protected $casts = [
        'is_weekday' => 'boolean',
        // 'overtime_id' => 'integer',
        // 'start_hour' => 'integer',
        // 'end_hour' => 'integer',
        // 'multiply' => 'integer',
    ];

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }
}
