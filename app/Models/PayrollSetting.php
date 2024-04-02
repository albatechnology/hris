<?php

namespace App\Models;

use App\Enums\DefaultEmployeeTaxSetting;
use App\Enums\JhtCost;
use App\Enums\ProrateSetting;
use App\Enums\TaxSalary;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'payroll_schedule_date',
        'cutoff_attendance_start_date',
        'cutoff_attendance_end_date',
        'default_employee_tax_setting',
        'default_employee_salary_tax_setting',
        'default_oas_setting',
        // 'is_default_cutoff',
        // 'is_attendance_pay_last_month',
        // 'cutoff_payroll_end_date',
        // 'cutoff_payroll_end_date',
        'prorate_setting',
        'is_count_national_holiday_as_working_day',
    ];

    protected $casts = [
        'payroll_schedule_date' => 'integer',
        'default_employee_tax_setting' => DefaultEmployeeTaxSetting::class,
        'default_employee_salary_tax_setting' => TaxSalary::class,
        'default_oas_setting' => JhtCost::class,
        'prorate_setting' => ProrateSetting::class,
        'is_count_national_holiday_as_working_day' => 'boolean',
    ];
}
