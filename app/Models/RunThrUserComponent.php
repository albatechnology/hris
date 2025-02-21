<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunThrUserComponent extends BaseModel
{
    protected $fillable = [
        'run_thr_user_id',
        'payroll_component_id',
        'amount',
        'is_editable',
        'payroll_component',
    ];

    protected $casts = [
        'run_thr_user_id' => 'integer',
        'payroll_component_id' => 'integer',
        'amount' => 'double',
        'is_editable' => 'boolean',
        'payroll_component' => 'array',
    ];

    public function runThrUser(): BelongsTo
    {
        return $this->belongsTo(RunThrUser::class);
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
