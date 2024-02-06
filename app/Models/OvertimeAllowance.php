<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeAllowance extends BaseModel
{
    protected $fillable = [
        'overtime_id',
        'payroll_category_id',
        'amount',
    ];

    protected $casts = [
        'overtime_id' => 'integer',
        'payroll_category_id' => 'integer',
        'rate_amount' => 'float',
    ];

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }
}
