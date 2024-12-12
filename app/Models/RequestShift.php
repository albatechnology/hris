<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestShift extends RequestedBaseModel implements TenantedInterface
{
    use BelongsToUser, TenantedThroughUser;

    protected $fillable = [
        'user_id',
        'schedule_id',
        'old_shift_id',
        'new_shift_id',
        'date',
        'description',
    ];

    protected static function booted(): void
    {
        parent::booted();
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function oldShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'old_shift_id');
    }

    public function newShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'new_shift_id');
    }
}
