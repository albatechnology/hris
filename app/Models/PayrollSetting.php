<?php

namespace App\Models;

use App\Enums\DefaultEmployeeTaxSetting;
use App\Enums\JhtCost;
use App\Enums\TaxSalary;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'cutoff_attendance_start_date',
        'cutoff_attendance_end_date',
        'default_employee_tax_setting',
        'default_employee_salary_tax_setting',
        'default_oas_setting',
        // 'payroll_schedule',
        // 'is_default_cutoff',
        // 'is_attendance_pay_last_month',
        // 'cutoff_payroll_end_date',
        // 'cutoff_payroll_end_date',
    ];

    protected $casts = [
        'default_employee_tax_setting' => DefaultEmployeeTaxSetting::class,
        'default_employee_salary_tax_setting' => TaxSalary::class,
        'default_oas_setting' => JhtCost::class,
    ];
}
