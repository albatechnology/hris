<?php

namespace App\Models;

use App\Enums\TimeoffPolicyBalanceAllocationFrequency;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeoffPolicy extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes, CompanyTenanted, CreatedUpdatedInfo;

    protected $fillable = [
        // phase 1
        'company_id',
        // 'type',
        'name',
        'code',
        'description',
        'effective_date',

        // phase 2
        'is_has_balance',
        'balance_allocation_frequency',

        'renewal_date_day', // used when balance_allocation_frequency is annualy
        'renewal_date_month', // used when balance_allocation_frequency is annualy

        'default_quota', // in talenta is base_balance`bere
        'is_enable_block_leave',
        'block_leave_take_days', // filled when is_enable_block_leave is true

        // phase 3
        // 'is_show_in_request_form', from talenta but not required for now
        'is_allow_halfday_request', // from is_allow_halfday
        'max_consecutively_day',
        'is_required_file_attachment',
        'is_allow_negative_balance', // allow for hutang cuti
        // 'expired_date',
        // 'expired_date_day',
        // 'expired_date_month',
        // 'expired_date_year',
        // 'is_for_all_user',
        // 'block_leave_min_working_month',
        // 'max_used',
    ];

    protected $casts = [
        'balance_allocation_frequency' => TimeoffPolicyBalanceAllocationFrequency::class,
        'is_allow_halfday' => 'boolean',
        // 'type' => TimeoffPolicyType::class,
        // 'is_for_all_user' => 'boolean',
        'is_enable_block_leave' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->code)) {
                $model->code = collect(explode(' ', $model->name))->map(fn($name) => strtoupper($name[0]))->join('');
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_timeoff_policies');
    }

    public function timeoffQuotas(): HasMany
    {
        return $this->hasMany(TimeoffQuota::class);
    }

    public function scopeStartEffectiveDate(Builder $query, $date = null)
    {
        if (is_null($date)) {
            return $query;
        }
        $query->whereDate('effective_date', '>=', date('Y-m-d', strtotime($date)));
    }


    public function scopeEndEffectiveDate(Builder $query, $date = null)
    {
        if (is_null($date)) {
            return $query;
        }
        $query->whereDate('effective_date', '<=', date('Y-m-d', strtotime($date)));
    }

    public function scopeWhereActive(Builder $query, ?bool $isActive = true)
    {
        if (is_null($isActive)) {
            return $query;
        }
        if ($isActive) {
            return $query->whereDate('effective_date', '<=', date('Y-m-d'))->whereDate('expired_date', '>=', date('Y-m-d'))->orWhere(fn($q) => $q->whereNull('effective_date')->orWhereNull('expired_date'));
        }

        $query->whereDate('effective_date', '>=', date('Y-m-d'))->orWhereDate('expired_date', '<=', date('Y-m-d'));
    }

    public function scopeSelectMinimalist(Builder $query, array $additionalColumns = [])
    {
        $query->select(['id', 'name', 'code', ...$additionalColumns]);
    }
}
