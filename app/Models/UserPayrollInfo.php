<?php

namespace App\Models;

use App\Enums\BpjsKesehatanCost;
use App\Enums\CostCenterCategory;
use App\Enums\CurrencyCode;
use App\Enums\EmploymentStatus;
use App\Enums\JaminanPensiunCost;
use App\Enums\JhtCost;
use App\Enums\NppBpjsKetenagakerjaan;
use App\Enums\OvertimeSetting;
use App\Enums\PaymentSchedule;
use App\Enums\ProrateSetting;
use App\Enums\PtkpStatus;
use App\Enums\SalaryType;
use App\Enums\TaxMethod;
use App\Enums\TaxSalary;
use App\Traits\Models\BelongsToUser;

class UserPayrollInfo extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
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
        'npwp',
        'ptkp_status',
        'tax_method',
        'tax_salary',
        'taxable_date',
        'employee_tax_status',
        'beginning_netto',
        'pph21_paid',
        'bpjs_ketenagakerjaan_no',
        'npp_bpjs_ketenagakerjaan',
        'bpjs_ketenagakerjaan_date',
        'bpjs_kesehatan_no',
        'bpjs_kesehatan_family_no',
        'bpjs_kesehatan_date',
        'bpjs_kesehatan_cost',
        'jht_cost',
        'jaminan_pensiun_cost',
        'jaminan_pensiun_date',

    ];

    protected $casts = [
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
        'npp_bpjs_ketenagakerjaan' => NppBpjsKetenagakerjaan::class,
        'bpjs_kesehatan_cost' => BpjsKesehatanCost::class,
        'jht_cost' => JhtCost::class,
        'jaminan_pensiun_cost' => JaminanPensiunCost::class,
    ];
}
