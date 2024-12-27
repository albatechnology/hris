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
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPayrollInfo extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'total_working_days',
        'basic_salary',
        'salary_type',
        'payment_schedule',
        'prorate_setting',
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
        'tax_salary',
        'taxable_date',
        'employee_tax_status',
        'beginning_netto',
        'pph21_paid',
    ];

    protected $casts = [
        'basic_salary' => 'float',
        'salary_type' => SalaryType::class,
        'payment_schedule' => PaymentSchedule::class,
        'prorate_setting' => ProrateSetting::class,
        'overtime_setting' => OvertimeSetting::class,
        'cost_center_category' => CostCenterCategory::class,
        'currency' => CurrencyCode::class,
        'ptkp_status' => PtkpStatus::class,
        'tax_method' => TaxMethod::class,
        'tax_salary' => TaxSalary::class,
        'employee_tax_status' => EmploymentStatus::class,
    ];

    public function components(): HasMany
    {
        return $this->hasMany(UserPayrollInfoComponent::class);
    }
}
