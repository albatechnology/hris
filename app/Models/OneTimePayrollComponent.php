<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OneTimePayrollComponent extends BaseModel
{
    protected $fillable = [
        'user_id',
        'payroll_component_id',
        'run_payroll_id',
    ];

    public function runPayroll(): BelongsTo
    {
        return $this->belongsTo(RunPayroll::class);
    }
}
