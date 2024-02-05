<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeoffPeriodRegulation extends BaseModel
{
    protected $fillable = [
        'timeoff_regulation_id',
        'min_working_month',
        'max_working_month',
    ];

    public function timeoffRegulation(): BelongsTo
    {
        return $this->belongsTo(TimeoffRegulation::class);
    }
}
