<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunPayrollUser extends BaseModel
{
    protected $fillable = [
        'run_payroll_id',
        'user_id',
        'basic_salary',
        'gross_salary',
        'allowance',
        'additional_earning',
        'deduction',
        'benefit',
        'tax',
        'payroll_info',
    ];

    protected $casts = [
        'run_payroll_id' => 'integer',
        'user_id' => 'integer',
        'basic_salary' => 'double',
        'gross_salary' => 'double',
        'allowance' => 'double',
        'additional_earning' => 'double',
        'deduction' => 'double',
        'benefit' => 'double',
        'tax' => 'double',
        'payroll_info' => 'array',
    ];

    protected $appends = [
        'thp',
        'total_earning',
        'total_deduction',
    ];

    public function getThpAttribute()
    {
        return $this->total_earning - $this->total_deduction;
    }

    public function getTotalEarningAttribute()
    {
        return $this->basic_salary + $this->allowance + $this->additional_earning;
    }

    public function getTotalDeductionAttribute()
    {
        return $this->deduction + $this->tax;
    }

    public function runPayroll(): BelongsTo
    {
        return $this->belongsTo(RunPayroll::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(RunPayrollUserComponent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
