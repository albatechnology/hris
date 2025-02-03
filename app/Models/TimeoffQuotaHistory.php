<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeoffQuotaHistory extends BaseModel implements TenantedInterface
{
    use BelongsToUser, TenantedThroughUser, SoftDeletes, CreatedUpdatedInfo;

    protected $fillable = [
        'user_id',
        'timeoff_quota_id',
        'is_increment',
        'old_balance',
        'new_balance',
        'description',
    ];

    protected $casts = [
        'is_increment' => 'boolean',
        'old_balance' => 'float',
        'new_balance' => 'float',
    ];

    protected $appends = ['balance'];

    public function getBalanceAttribute(): float
    {
        return $this->is_increment ? ($this->new_balance - $this->old_balance) : ($this->old_balance - $this->new_balance);
    }

    public function timeoffQuota(): BelongsTo
    {
        return $this->belongsTo(TimeoffQuota::class);
    }

    public function scopeSearch(Builder $query, string $value)
    {
        $query->whereHas('user', fn($q) => $q->whereName($value));
    }
}
