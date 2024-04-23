<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDayoff extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'timeoff_policy_id',
        'total_amount',
        'expired_at',
        'used_amount',
        'approval_status',
        'approved_by',
        'approved_at',
        'note',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'used_amount' => 'float',
        'approval_status' => ApprovalStatus::class,
    ];

    public function timeoffPolicy(): BelongsTo
    {
        return $this->belongsTo(TimeoffPolicy::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_status');
    }
}
