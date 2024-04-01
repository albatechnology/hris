<?php

namespace App\Models;

use App\Enums\PayrollComponentIncludedType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollComponentInclude extends BaseModel
{
    protected $fillable = [
        'payroll_component_id',
        'included_payroll_component_id',
        'type',
    ];

    protected $casts = [
        'payroll_component_id' => 'integer',
        'included_payroll_component_id' => 'integer',
        'type' => PayrollComponentIncludedType::class,
    ];

    public function includedPayrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'included_payroll_component_id', 'id');
    }
}
