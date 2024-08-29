<?php

namespace App\Models;

use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patrol extends BaseModel
{
    use CustomSoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'lat',
        'lng',
        'description',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(PatrolLocation::class);
    }
}
