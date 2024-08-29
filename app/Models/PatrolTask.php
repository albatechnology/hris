<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatrolTask extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'patrol_location_id',
        'name',
        'description',
    ];

    public function patrolLocation(): BelongsTo
    {
        return $this->belongsTo(PatrolLocation::class);
    }
}
