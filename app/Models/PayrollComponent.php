<?php

namespace App\Models;

use App\Enums\PayrollComponentDailyMaximumAmountType;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentType;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\MorphManyFormulas;

class PayrollComponent extends BaseModel
{
    use CompanyTenanted, MorphManyFormulas;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'amount',
        'is_taxable',
        'period_type',
        'is_monthly_prorate',
        'is_daily_default',
        'daily_maximum_amount_type',
        'daily_maximum_amount',
        'is_one_time_bonus',
        'is_default',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'name' => 'string',
        'type' => PayrollComponentType::class,
        'amount' => 'double',
        'is_taxable' => 'boolean',
        'period_type' => PayrollComponentPeriodType::class,
        'is_monthly_prorate' => 'boolean',
        'is_daily_default' => 'boolean',
        'daily_maximum_amount_type' => PayrollComponentDailyMaximumAmountType::class,
        'daily_maximum_amount' => 'double',
        'is_one_time_bonus' => 'boolean',
        'is_default' => 'boolean',
    ];
}
