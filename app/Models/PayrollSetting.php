<?php

namespace App\Models;

use App\Enums\TaxMethod;
use App\Enums\JhtCost;
use App\Enums\ProrateSetting;
use App\Enums\TaxSalary;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToBranch;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model implements TenantedInterface
{
    use CompanyTenanted, BelongsToBranch;

    protected $fillable = [
        'company_id',
        // 'client_id',
        'branch_id',
        'cut_off_attendance_start_date',
        'cut_off_attendance_end_date',
        'payroll_start_date',
        'payroll_end_date',
        'cut_off_date',
        'is_attendance_pay_last_month',
        'default_employee_tax_setting',
        'default_employee_salary_tax_setting',
        'default_oas_setting',
        'prorate_setting',
        'is_count_national_holiday_as_working_day',
    ];

    protected $casts = [
        'default_employee_tax_setting' => TaxMethod::class,
        'default_employee_salary_tax_setting' => TaxSalary::class,
        'default_oas_setting' => JhtCost::class,
        'prorate_setting' => ProrateSetting::class,
        'is_count_national_holiday_as_working_day' => 'boolean',
        'is_attendance_pay_last_month' => 'boolean',
    ];
}
