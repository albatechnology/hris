<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeAllowance extends BaseModel
{
    protected $fillable = [
        'overtime_id',
        'payroll_component_id',
        'amount',
    ];

    protected $casts = [
        'overtime_id' => 'integer',
        'payroll_component_id' => 'integer',
        'amount' => 'double',
    ];

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
