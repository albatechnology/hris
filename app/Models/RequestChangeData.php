<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RequestChangeData extends RequestedBaseModel implements HasMedia, TenantedInterface
{
    use BelongsToUser, InteractsWithMedia, TenantedThroughUser;

    protected $table = 'request_change_data';

    protected $fillable = [
        'user_id',
        'description',
        // 'approval_status', moved to approvals
        // 'approved_by',
        // 'approved_at',
    ];

    // protected $appends = [
    //     'approval_status'
    // ];

    // protected $casts = [
    // 'approval_status' => ApprovalStatus::class moved to approvals
    // ];

    protected static function booted(): void
    {
        parent::booted();

        // static::creating(function (self $model) {
        // $model->approved_by = $model->user->approval?->id ?? null;
        // });
    }

    public function details(): HasMany
    {
        return $this->hasMany(RequestChangeDataDetail::class);
    }

    // public function approvedBy(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }
}
