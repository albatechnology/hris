<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeoffRegulationMonth extends BaseModel
{
    protected $fillable = [
        // 'user_id',
        'timeoff_period_regulation_id',
        'month',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function timeoffPeriodRegulation(): BelongsTo
    {
        return $this->belongsTo(TimeoffPeriodRegulation::class);
    }
}
