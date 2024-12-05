<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvancedLeaveRequest extends BaseModel implements TenantedInterface
{
    use TenantedThroughUser;

    protected $fillable = [
        'user_id',
        'data',
        'amount',
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'data' => 'array',
        'amount' => 'float',
        'approval_status' => ApprovalStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->approved_by = $model->user->approval?->id ?? null;
        });
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
