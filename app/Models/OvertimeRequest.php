<?php

namespace App\Models;

use App\Enums\OvertimeStatus;
use App\Observers\OvertimeRequestObserver;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([OvertimeRequestObserver::class])]
class OvertimeRequest extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'date',
        'shift_id',
        'overtime_id',
        'start_at',
        'end_at',
        'note',
        'status',
        'status_updated_by',
        'status_changed_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'date' => 'date',
        'shift_id' => 'integer',
        'overtime_id' => 'integer',
        'start_at' => 'datetime:H:i',
        'end_at' => 'datetime:H:i',
        'note' => 'string',
        'status' => OvertimeStatus::class,
        'status_updated_by' => 'integer',
        'status_updated_at' => 'datetime',
    ];

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function statusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_updated_by', 'id');
    }
}
