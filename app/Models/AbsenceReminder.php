<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToBranch;
use App\Traits\Models\TenantedThroughBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceReminder extends BaseModel implements TenantedInterface
{
    use TenantedThroughBranch, BelongsToBranch;

    protected $fillable = [
        'company_id',
        'branch_id',
        'is_active',
        'minutes_before',
        'minutes_repeat',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $model) {
            $model->updated_by_id = auth('api')->id();
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
