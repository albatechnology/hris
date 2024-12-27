<?php

namespace App\Models;

use App\Enums\CurrencyCode;
use App\Enums\JkkTier;
use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes;

    protected $fillable = [
        'group_id',
        'name',
        'country_id',
        'country',
        'province',
        'city',
        'zip_code',
        'lat',
        'lng',
        'address',
        'currency_code',
        'jkk_tier',
    ];

    protected $casts = [
        'currency_code' => CurrencyCode::class,
        'jkk_tier' => JkkTier::class,
    ];

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) return $query;

        // if ($user->is_admin) return $query->where('group_id', $user->group_id);

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        return $query->whereIn('id', $companyIds);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function timeoffPolicies(): HasMany
    {
        return $this->hasMany(TimeoffPolicy::class);
    }

    public function liveAttendances(): HasMany
    {
        return $this->hasMany(LiveAttendance::class);
    }

    public function payrollComponents(): HasMany
    {
        return $this->hasMany(PayrollComponent::class);
    }

    // public function timeoffRegulation(): HasOne
    // {
    //     return $this->hasOne(TimeoffRegulation::class);
    // }

    public function payrollSetting(): HasOne
    {
        return $this->hasOne(PayrollSetting::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function countryTable(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function npp(): HasOne
    {
        return $this->hasOne(Npp::class);
    }

    public function supervisorType(): BelongsTo
    {
        return $this->belongsTo(SupervisorType::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function createPayrollSetting(): void
    {
        $this->payrollSetting()->create([
            'company_id' => $this->id,
            'cut_off_date' => '20',
            // 'cutoff_attendance_start_date' => '02',
            // 'cutoff_attendance_end_date' => '05',
            'default_employee_tax_setting' => \App\Enums\TaxMethod::GROSS,
            'default_employee_salary_tax_setting' => \App\Enums\TaxSalary::TAXABLE,
            'default_oas_setting' => \App\Enums\JhtCost::PAID_BY_COMPANY,
            'prorate_setting' => \App\Enums\ProrateSetting::BASE_ON_CALENDAR_DAY,
        ]);

        $this->payrollComponents()->create([
            'name' => 'Basic Salary',
            'type' => PayrollComponentType::ALLOWANCE,
            'category' => PayrollComponentCategory::BASIC_SALARY,
            'amount' => 0,
            'is_taxable' => true,
            'period_type' => PayrollComponentPeriodType::MONTHLY,
            'is_monthly_prorate' => false,
            'is_include_backpay' => false,
            'is_default' => true,
        ]);

        $this->payrollComponents()->create([
            'name' => 'Overtime',
            'type' => PayrollComponentType::ALLOWANCE,
            'category' => PayrollComponentCategory::OVERTIME,
            'amount' => 0,
            'is_taxable' => true,
            'period_type' => PayrollComponentPeriodType::MONTHLY,
            'is_monthly_prorate' => false,
            'is_include_backpay' => false,
            'is_default' => true,
        ]);

        $this->payrollComponents()->create([
            'name' => 'Alpa',
            'type' => PayrollComponentType::DEDUCTION,
            'category' => PayrollComponentCategory::ALPA,
            'amount' => 0,
            'is_taxable' => false,
            'period_type' => PayrollComponentPeriodType::MONTHLY,
            'is_monthly_prorate' => false,
            'is_include_backpay' => false,
            'is_default' => true,
        ]);

        if ($this->countryTable?->id == 1) {
            $this->payrollComponents()->create([
                'name' => 'BPJS Kesehatan',
                'type' => PayrollComponentType::BENEFIT,
                'category' => PayrollComponentCategory::BPJS_KESEHATAN,
                'amount' => 0,
                'is_taxable' => true,
                'period_type' => PayrollComponentPeriodType::MONTHLY,
                'is_monthly_prorate' => false,
                'is_include_backpay' => false,
                'is_default' => true,
            ]);

            $this->payrollComponents()->create([
                'name' => 'BPJS Ketenagakerjaan',
                'type' => PayrollComponentType::BENEFIT,
                'category' => PayrollComponentCategory::BPJS_KETENAGAKERJAAN,
                'amount' => 0,
                'is_taxable' => true,
                'period_type' => PayrollComponentPeriodType::MONTHLY,
                'is_monthly_prorate' => false,
                'is_include_backpay' => false,
                'is_default' => true,
            ]);


            $this->payrollComponents()->create([
                'name' => 'BPJS Kesehatan Company',
                'type' => PayrollComponentType::BENEFIT,
                'category' => PayrollComponentCategory::COMPANY_BPJS_KESEHATAN,
                'amount' => 0,
                'is_taxable' => true,
                'period_type' => PayrollComponentPeriodType::MONTHLY,
                'is_monthly_prorate' => false,
                'is_include_backpay' => false,
                'is_default' => true,
                'is_hidden' => true,
            ]);

            $this->payrollComponents()->create([
                'name' => 'BPJS Kesehatan Employee',
                'type' => PayrollComponentType::DEDUCTION,
                'category' => PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN,
                'amount' => 0,
                'is_taxable' => true,
                'period_type' => PayrollComponentPeriodType::MONTHLY,
                'is_monthly_prorate' => false,
                'is_include_backpay' => false,
                'is_default' => true,
                'is_hidden' => true,
            ]);

            $this->payrollComponents()->create([
                'name' => 'JKK',
                'type' => PayrollComponentType::BENEFIT,
                'category' => PayrollComponentCategory::COMPANY_JKK,
                'amount' => 0,
                'is_taxable' => true,
                'period_type' => PayrollComponentPeriodType::MONTHLY,
                'is_monthly_prorate' => false,
                'is_include_backpay' => false,
                'is_default' => true,
                'is_hidden' => true,
            ]);

            $this->payrollComponents()->create([
                'name' => 'JKM',
                'type' => PayrollComponentType::BENEFIT,
                'category' => PayrollComponentCategory::COMPANY_JKM,
                'amount' => 0,
                'is_taxable' => true,
                'period_type' => PayrollComponentPeriodType::MONTHLY,
                'is_monthly_prorate' => false,
                'is_include_backpay' => false,
                'is_default' => true,
                'is_hidden' => true,
            ]);

            $this->payrollComponents()->create([
                'name' => 'JHT Company',
                'type' => PayrollComponentType::BENEFIT,
                'category' => PayrollComponentCategory::COMPANY_JHT,
                'amount' => 0,
                'is_taxable' => true,
                'period_type' => PayrollComponentPeriodType::MONTHLY,
                'is_monthly_prorate' => false,
                'is_include_backpay' => false,
                'is_default' => true,
                'is_hidden' => true,
            ]);

            $this->payrollComponents()->create([
                'name' => 'JHT Employee',
                'type' => PayrollComponentType::DEDUCTION,
                'category' => PayrollComponentCategory::EMPLOYEE_JHT,
                'amount' => 0,
                'is_taxable' => true,
                'period_type' => PayrollComponentPeriodType::MONTHLY,
                'is_monthly_prorate' => false,
                'is_include_backpay' => false,
                'is_default' => true,
                'is_hidden' => true,
            ]);

            $this->payrollComponents()->create([
                'name' => 'JP Company',
                'type' => PayrollComponentType::BENEFIT,
                'category' => PayrollComponentCategory::COMPANY_JP,
                'amount' => 0,
                'is_taxable' => true,
                'period_type' => PayrollComponentPeriodType::MONTHLY,
                'is_monthly_prorate' => false,
                'is_include_backpay' => false,
                'is_default' => true,
                'is_hidden' => true,
            ]);

            $this->payrollComponents()->create([
                'name' => 'JP Employee',
                'type' => PayrollComponentType::DEDUCTION,
                'category' => PayrollComponentCategory::EMPLOYEE_JP,
                'amount' => 0,
                'is_taxable' => true,
                'period_type' => PayrollComponentPeriodType::MONTHLY,
                'is_monthly_prorate' => false,
                'is_include_backpay' => false,
                'is_default' => true,
                'is_hidden' => true,
            ]);
        }
    }
}
