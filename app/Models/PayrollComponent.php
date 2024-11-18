<?php

namespace App\Models;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentDailyMaximumAmountType;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentSetting;
use App\Enums\PayrollComponentType;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\MorphManyFormulas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollComponent extends BaseModel
{
    use CompanyTenanted, MorphManyFormulas, CustomSoftDeletes, CreatedUpdatedInfo;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'category',
        'setting',
        'amount',
        'is_taxable',
        'period_type',
        'is_monthly_prorate',
        'is_daily_default',
        'daily_maximum_amount_type',
        'daily_maximum_amount',
        'is_one_time_bonus',
        'is_include_backpay',
        'is_default',
        'is_hidden',
        // 'is_calculateable',
    ];

    protected $casts = [
        'type' => PayrollComponentType::class,
        'category' => PayrollComponentCategory::class,
        'setting' => PayrollComponentSetting::class,
        'amount' => 'double',
        'is_taxable' => 'boolean',
        'period_type' => PayrollComponentPeriodType::class,
        'is_monthly_prorate' => 'boolean',
        'is_daily_default' => 'boolean',
        'daily_maximum_amount_type' => PayrollComponentDailyMaximumAmountType::class,
        'daily_maximum_amount' => 'double',
        'is_one_time_bonus' => 'boolean',
        'is_include_backpay' => 'boolean',
        'is_default' => 'boolean',
        'is_hidden' => 'boolean',
        // 'is_calculateable' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->daily_maximum_amount_type) {
                $model->daily_maximum_amount_type = PayrollComponentDailyMaximumAmountType::NOT_USE;
            }
        });
    }

    public function includes(): HasMany
    {
        return $this->hasMany(PayrollComponentInclude::class);
    }

    public function scopeWhereNotBpjs(Builder $query): void
    {
        $query->whereNotIn('category', [
            PayrollComponentCategory::BPJS_KESEHATAN,
            PayrollComponentCategory::BPJS_KETENAGAKERJAAN,
        ]);
    }

    public function scopeWhereDefault(Builder $query): void
    {
        $query->where('is_default', true)->whereNotIn('category', [
            PayrollComponentCategory::OVERTIME,
            PayrollComponentCategory::BPJS_KESEHATAN,
            PayrollComponentCategory::BPJS_KETENAGAKERJAAN,
            PayrollComponentCategory::COMPANY_BPJS_KESEHATAN,
            PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN,
            PayrollComponentCategory::COMPANY_JKK,
            PayrollComponentCategory::COMPANY_JKM,
            PayrollComponentCategory::COMPANY_JHT,
            PayrollComponentCategory::EMPLOYEE_JHT,
            PayrollComponentCategory::COMPANY_JP,
            PayrollComponentCategory::EMPLOYEE_JP,
        ]);
    }

    public function scopeWhereBpjs(Builder $query): void
    {
        $query->where('is_default', true)->whereIn('category', [
            // PayrollComponentCategory::BPJS_KESEHATAN,
            // PayrollComponentCategory::BPJS_KETENAGAKERJAAN,
            PayrollComponentCategory::COMPANY_BPJS_KESEHATAN,
            PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN,
            PayrollComponentCategory::COMPANY_JKK,
            PayrollComponentCategory::COMPANY_JKM,
            PayrollComponentCategory::COMPANY_JHT,
            PayrollComponentCategory::EMPLOYEE_JHT,
            PayrollComponentCategory::COMPANY_JP,
            PayrollComponentCategory::EMPLOYEE_JP,
        ]);
    }
}
