<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvancedLeaveRequest extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'months',
        'amount',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'months' => 'array',
        'amount' => 'float',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
