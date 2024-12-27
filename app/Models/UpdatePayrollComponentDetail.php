<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdatePayrollComponentDetail extends BaseModel
{
    use BelongsToUser;

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

    public function updatePayrollComponent(): BelongsTo
    {
        return $this->belongsTo(UpdatePayrollComponent::class);
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
