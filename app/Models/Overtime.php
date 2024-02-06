<?php

namespace App\Models;

use App\Enums\RateType;
use App\Traits\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Overtime extends BaseModel
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'name',
        'is_rounding',
        'compensation_rate_per_day',
        'rate_type',
        'rate_amount',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'name' => 'string',
        'is_rounding' => 'boolean',
        'compensation_rate_per_day' => 'integer',
        'rate_type' => RateType::class,
        'rate_amount' => 'float',
    ];

    public function overtimeAllowances(): HasMany
    {
        return $this->hasMany(OvertimeAllowance::class);
    }

    public function overtimeMultiplier(): HasMany
    {
        return $this->hasMany(OvertimeMultiplier::class);
    }

    public function overtimeRequest(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function overtimeRounding(): HasMany
    {
        return $this->hasMany(OvertimeRounding::class);
    }
}
