<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunPayrollUserComponent extends BaseModel
{
    protected $fillable = [
        'run_payroll_user_id',
        'payroll_component_id',
        'amount',
        'is_editable',
        'payroll_component',
        'context',
    ];

    protected $casts = [
        'run_payroll_user_id' => 'integer',
        'payroll_component_id' => 'integer',
        'amount' => 'double',
        'is_editable' => 'boolean',
        'payroll_component' => 'array',
        'context' => 'array',
    ];

    public function runPayrollUser(): BelongsTo
    {
        return $this->belongsTo(RunPayrollUser::class);
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
