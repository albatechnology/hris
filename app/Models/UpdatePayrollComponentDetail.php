<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdatePayrollComponentDetail extends BaseModel
{
    protected $fillable = [
        'update_payroll_component_id',
        'user_id',
        'payroll_component_id',
        'current_amount',
        'new_amount',
    ];

    protected $casts = [
        'update_payroll_component_id' => 'integer',
        'user_id' => 'integer',
        'payroll_component_id' => 'integer',
        'current_amount' => 'double',
        'new_amount' => 'double',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
