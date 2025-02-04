<?php

namespace App\Models;

use App\Enums\CostCenterCategory;
use App\Enums\CurrencyCode;
use App\Enums\EmploymentStatus;
use App\Enums\OvertimeSetting;
use App\Enums\PaymentSchedule;
use App\Enums\ProrateSetting;
use App\Enums\PtkpStatus;
use App\Enums\SalaryType;
use App\Enums\TaxMethod;
use App\Enums\TaxSalary;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPayrollInfo extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'payroll_branch_id',
        'total_working_days',
        'is_ignore_alpa',
        'basic_salary',
        'salary_type', // not used yet
        'payment_schedule', // not used yet
        'prorate_setting', // not used yet
        'overtime_setting',
        'cost_center_category',
        'currency',
        'bank_name',
        'bank_account_no',
        'bank_account_holder',
        'secondary_bank_name',
        'secondary_bank_account_no',
        'secondary_bank_account_holder',
        'npwp',
        'ptkp_status',
        'tax_method',
        'tax_salary', // not used yet
        'taxable_date', // not used yet
        'employee_tax_status', // not used yet
        'beginning_netto', // not used yet
        'pph21_paid', // not used yet
    ];

    protected $casts = [
        'total_working_days' => 'integer',
        'is_ignore_alpa' => 'boolean',
        'basic_salary' => 'float',
        'salary_type' => SalaryType::class, // not used yet
        'payment_schedule' => PaymentSchedule::class, // not used yet
        'prorate_setting' => ProrateSetting::class, // not used yet
        'overtime_setting' => OvertimeSetting::class,
        'cost_center_category' => CostCenterCategory::class,
        'currency' => CurrencyCode::class, // not used yet
        'ptkp_status' => PtkpStatus::class,
        'tax_method' => TaxMethod::class,
        'tax_salary' => TaxSalary::class, // not used yet
        'employee_tax_status' => EmploymentStatus::class,
    ];

    public function components(): HasMany
    {
        return $this->hasMany(UserPayrollInfoComponent::class);
    }

    public function payrollBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'payroll_branch_id');
    }
}
