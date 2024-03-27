<?php

namespace App\Models;

class UserPayrollInfoComponent extends BaseModel
{
    protected $fillable = [
        'user_payroll_info_id',
        'payroll_component_id',
        'amount',
    ];

    protected $casts = [
        'user_payroll_info_id' => 'integer',
        'payroll_component_id' => 'integer',
        'amount' => 'double',
    ];
}
