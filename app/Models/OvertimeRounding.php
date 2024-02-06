<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRounding extends BaseModel
{
    protected $fillable = [
        'overtime_id',
        'start_minute',
        'end_minute',
        'rounded',
    ];

    protected $casts = [
        'overtime_id' => 'integer',
        'start_minute' => 'integer',
        'end_minute' => 'integer',
        'rounded' => 'integer',
    ];

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }
}
