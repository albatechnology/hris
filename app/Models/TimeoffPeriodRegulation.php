<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeoffPeriodRegulation extends BaseModel
{
    protected $fillable = [
        'timeoff_regulation_id',
        'min_working_month',
        'max_working_month',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            $model->timeoffRegulationMonths()->delete();
        });
    }

    public function timeoffRegulationMonths(): HasMany
    {
        return $this->hasMany(TimeoffRegulationMonth::class);
    }

    public function timeoffRegulation(): BelongsTo
    {
        return $this->belongsTo(TimeoffRegulation::class);
    }
}
