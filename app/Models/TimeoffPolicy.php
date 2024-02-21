<?php

namespace App\Models;

use App\Enums\TimeoffPolicyType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TimeoffPolicy extends BaseModel implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'code',
        'description',
        'effective_date',
        'expired_date',
        'is_allow_halfday',
        'is_for_all_user',
        'is_unlimited_day',
        'is_enable_block_leave',
        'block_leave_take_days',
        'block_leave_min_working_month',
        'max_used',
    ];

    protected $casts = [
        'type' => TimeoffPolicyType::class,
        'is_allow_halfday' => 'boolean',
        'is_for_all_user' => 'boolean',
        'is_enable_block_leave' => 'boolean',
        'is_unlimited_day' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->code)) {
                $model->code = collect(explode(' ', $model->name))->map(fn ($name) => strtoupper($name[0]))->join('');
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_timeoff_policies');
    }

    public function scopeStartEffectiveDate(Builder $query, $date = null)
    {
        if (is_null($date)) return $query;
        $query->whereDate('effective_date', '>=', date('Y-m-d', strtotime($date)));
    }

    public function scopeEndEffectiveDate(Builder $query, $date = null)
    {
        if (is_null($date)) return $query;
        $query->whereDate('effective_date', '<=', date('Y-m-d', strtotime($date)));
    }

    public function scopeWhereActive(Builder $query, bool|null $isActive = true)
    {
        if (is_null($isActive)) return $query;
        if ($isActive) {
            return $query->whereDate('effective_date', '<=', date('Y-m-d'))->whereDate('expired_date', '>=', date('Y-m-d'))->orWhere(fn ($q) => $q->whereNull('effective_date')->orWhereNull('expired_date'));
        }

        $query->whereDate('effective_date', '>=', date('Y-m-d'))->orWhereDate('expired_date', '<=', date('Y-m-d'));
    }
}
