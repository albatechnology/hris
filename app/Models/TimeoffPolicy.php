<?php

namespace App\Models;

use App\Enums\TimeoffPolicyType;
use App\Interfaces\TenantedInterface;
use App\Traits\CompanyTenanted;
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
        'is_for_all_user',
        'is_enable_block_leave',
        'is_unlimited_day',
    ];

    protected $casts = [
        'type' => TimeoffPolicyType::class,
        'is_for_all_user' => 'boolean',
        'is_enable_block_leave' => 'boolean',
        'is_unlimited_day' => 'boolean',
    ];

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
}
