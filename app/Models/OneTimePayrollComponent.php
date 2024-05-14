<?php

namespace App\Models;

class OneTimePayrollComponent extends BaseModel
{
    protected $fillable = [
        'user_id',
        'payroll_component_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'payroll_component_id' => 'integer',
    ];
}
