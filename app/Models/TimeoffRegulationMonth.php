<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeoffRegulationMonth extends BaseModel
{
    protected $fillable = [
        'timeoff_regulation_id',
        'month',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function timeoffRegulation(): BelongsTo
    {
        return $this->belongsTo(TimeoffRegulation::class);
    }
}
