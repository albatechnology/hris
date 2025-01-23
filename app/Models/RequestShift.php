<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestShift extends RequestedBaseModel implements TenantedInterface
{
    use CustomSoftDeletes, CreatedUpdatedInfo, BelongsToUser, TenantedThroughUser;

    protected $fillable = [
        'user_id',
        'schedule_id',
        'old_shift_id',
        'new_shift_id',
        'date',
        'description',
        'is_for_replace',
    ];

    protected $casts = [
        'is_for_replace' => 'boolean',
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

    public function requestApproved()
    {
        if (date('Y-m-d', strtotime($this->date)) <= date('Y-m-d')) {
            Attendance::where('user_id', $this->user_id)->whereDate('date', $this->date)->update(['schedule_id' => $this->schedule_id, 'shift_id' => $this->new_shift_id]);
        }
    }
}
