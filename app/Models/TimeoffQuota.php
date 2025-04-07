<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeoffQuota extends BaseModel implements TenantedInterface
{
    use BelongsToUser, TenantedThroughUser, CustomSoftDeletes, CreatedUpdatedInfo;

    protected $fillable = [
        'timeoff_policy_id',
        'user_id',
        'effective_start_date',
        'effective_end_date',
        'quota',
        'used_quota'
    ];

    protected $casts = [
        'quota' => 'float',
        'used_quota' => 'float'
    ];

    protected $appends = ['balance'];

    public function getBalanceAttribute(): float
    {
        return $this->quota - $this->used_quota;
    }

    public function timeoffPolicy(): BelongsTo
    {
        return $this->belongsTo(TimeoffPolicy::class);
    }

    public function timeoffQuotaHistories(): HasMany
    {
        return $this->hasMany(TimeoffQuotaHistory::class);
    }

    public function scopeWhereExpired(Builder $query, $date = null)
    {
        if (!$date) {
            $date = date('Y-m-d');
        } else {
            $date = date('Y-m-d', strtotime($date));
        }

        $query->where(fn($q) => $q->whereNotNull('effective_end_date')->whereDate('effective_end_date', '<=', $date));
    }

    public function scopeEffectiveStartDate(Builder $query, $date = null)
    {
        if (is_null($date)) {
            return $query;
        }
        $query->whereDate('effective_start_date', '>=', date('Y-m-d', strtotime($date)));
    }


    public function scopeEffectiveEndDate(Builder $query, $date = null)
    {
        if (is_null($date)) {
            return $query;
        }
        $query->whereDate('effective_end_date', '<=', date('Y-m-d', strtotime($date)));
    }

    public function scopeWhereYear(Builder $query, ?string $year = null)
    {
        if (is_null($year)) {
            $year = date('Y');
        }

        $query->where(fn($q) => $q->whereYear('effective_start_date', $year)
            ->orWhereYear('effective_end_date', $year));
    }

    public function scopeWhereActive($q, ?string $startDate = null, ?string $endDate = null, bool $withActiveQuota = true)
    {
        if ($startDate) {
            $startDate = date('Y-m-d', strtotime($startDate));
        } else {
            $startDate = date('Y-m-d');
        }

        if ($endDate) {
            $endDate = date('Y-m-d', strtotime($endDate));
        } else {
            $endDate = $startDate;
        }

        $q->where(
            fn($q) => $q
                ->where(
                    fn($q) => $q->whereDate('effective_start_date', '<=', $startDate)->whereDate('effective_end_date', '>=', $endDate)
                )
                ->orWhere(
                    fn($q) => $q->whereDate('effective_start_date', '<=', $startDate)->whereNull('effective_end_date')
                )
        )
            ->when($withActiveQuota, fn($q) => $q->whereRaw('quota > used_quota'));
    }
}
