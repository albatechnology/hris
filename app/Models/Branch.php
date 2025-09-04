<?php

namespace App\Models;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'country',
        'province',
        'city',
        'zip_code',
        'lat',
        'lng',
        'address',
        'umk',

        'pic_name',
        'pic_email',
        'pic_phone',
    ];

    // protected static function booted(): void
    // {
    //     static::created(function (self $model) {

    //     });
    // }

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) {
            return $query;
        }

        // $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
        // $query->whereIn('company_id', $companyIds);

        if ($user->is_admin) {
            // return $query;
            return $query->whereHas('company', fn($q) => $q->where('group_id', $user->group_id));
        }

        $branchIds = $user->branches()->get(['branch_id'])?->pluck('branch_id') ?? [];

        return $query->whereIn('id', $branchIds);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function payrollComponents(): HasMany
    {
        return $this->hasMany(PayrollComponent::class);
    }

    public function scopeSelectMinimalist(Builder $query, array $additionalColumns = [])
    {
        $query->select([
            'branches.id',
            'branches.company_id',
            'branches.name',
            'branches.address',
            'branches.pic_name',
            'branches.pic_email',
            'branches.pic_phone',
            'branches.created_at',
            ...$additionalColumns
        ]);
    }

    public function createPayrollSetting(): void
    {
        PayrollSetting::create([
            'company_id' => $this->company_id,
            'branch_id' => $this->id,
            'cut_off_attendance_start_date' => '01',
            'cut_off_attendance_end_date' => '31',
            'payroll_start_date' => '01',
            'payroll_end_date' => '31',
            'cut_off_date' => '20',
            'default_employee_tax_setting' => \App\Enums\TaxMethod::GROSS,
            'default_employee_salary_tax_setting' => \App\Enums\TaxSalary::TAXABLE,
            'default_oas_setting' => \App\Enums\JhtCost::PAID_BY_COMPANY,
            'prorate_setting' => \App\Enums\ProrateSetting::BASE_ON_CALENDAR_DAY,
        ]);

        $this->payrollComponents()->create([
            'company_id' => $this->company_id,
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
            'company_id' => $this->company_id,
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
            'name' => 'Reimbursement',
            'type' => PayrollComponentType::ALLOWANCE,
            'category' => PayrollComponentCategory::REIMBURSEMENT,
            'amount' => 0,
            'is_taxable' => true,
            'period_type' => PayrollComponentPeriodType::MONTHLY,
            'is_monthly_prorate' => false,
            'is_include_backpay' => false,
            'is_default' => true,
        ]);

        // $this->payrollComponents()->create([
        // 'company_id' => $this->company_id,
        //     'name' => 'Task Overtime',
        //     'type' => PayrollComponentType::ALLOWANCE,
        //     'category' => PayrollComponentCategory::TASK_OVERTIME,
        //     'amount' => 0,
        //     'is_taxable' => true,
        //     'period_type' => PayrollComponentPeriodType::MONTHLY,
        //     'is_monthly_prorate' => false,
        //     'is_include_backpay' => false,
        //     'is_default' => true,
        // ]);

        $this->payrollComponents()->create([
            'company_id' => $this->company_id,
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

        // $this->payrollComponents()->create([
        // 'company_id' => $this->company_id,
        //     'name' => 'Loan',
        //     'type' => PayrollComponentType::DEDUCTION,
        //     'category' => PayrollComponentCategory::LOAN,
        //     'amount' => 0,
        //     'is_taxable' => false,
        //     'period_type' => PayrollComponentPeriodType::MONTHLY,
        //     'is_monthly_prorate' => false,
        //     'is_include_backpay' => false,
        //     'is_default' => true,
        // ]);

        // $this->payrollComponents()->create([
        // 'company_id' => $this->company_id,
        //     'name' => 'Insurance',
        //     'type' => PayrollComponentType::DEDUCTION,
        //     'category' => PayrollComponentCategory::INSURANCE,
        //     'amount' => 0,
        //     'is_taxable' => false,
        //     'period_type' => PayrollComponentPeriodType::MONTHLY,
        //     'is_monthly_prorate' => false,
        //     'is_include_backpay' => false,
        //     'is_default' => true,
        // ]);

        if ($this->company->countryTable?->id == 1) {
            $this->payrollComponents()->create([
                'company_id' => $this->company_id,
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
                'company_id' => $this->company_id,
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
                'company_id' => $this->company_id,
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
                'company_id' => $this->company_id,
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
                'company_id' => $this->company_id,
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
                'company_id' => $this->company_id,
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
                'company_id' => $this->company_id,
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
                'company_id' => $this->company_id,
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
                'company_id' => $this->company_id,
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
                'company_id' => $this->company_id,
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
