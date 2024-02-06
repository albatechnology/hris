<?php

namespace App\Models;

use App\Enums\TimeoffRenewType;
use App\Interfaces\TenantedInterface;
use App\Traits\CompanyTenanted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeoffRegulation extends BaseModel implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'renew_type',
        'total_day',
        'start_period',
        'end_period',
        'max_consecutively_day',
        'is_allow_halfday',
        'halfday_not_applicable_in',
        'is_expired_in_end_period',
        'expired_max_month',
        'min_working_month',
        'cut_off_date',
        'min_advance_leave_working_month',
        'max_advance_leave_request',
        'dayoff_consecutively_working_day',
        'dayoff_consecutively_amount',
    ];

    protected $casts = [
        'renew_type' => TimeoffRenewType::class,
        'total_day' => 'float',
        'is_allow_halfday' => 'boolean',
        'halfday_not_applicable_in' => 'array',
        'is_expired_in_end_period' => 'boolean',
    ];

    public function timeoffRegulationMonths(): HasMany
    {
        return $this->hasMany(TimeoffRegulationMonth::class);
    }

    public function timeoffPeriodRegulations(): HasMany
    {
        return $this->hasMany(TimeoffPeriodRegulation::class);
    }

    public function scopeStartPeriod(Builder $query, $date = null)
    {
        if (is_null($date)) return $query;
        $query->whereDate('start_period', '>=', $date);
    }

    public function scopeEndPeriod(Builder $query, $date = null)
    {
        if (is_null($date)) return $query;
        $query->whereDate('end_period', '<=', $date);
    }
}
