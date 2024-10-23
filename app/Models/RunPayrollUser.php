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
        'allowance',
        'additional_earning',
        'deduction',
        'benefit',
        'tax',
    ];

    protected $casts = [
        'run_payroll_id' => 'integer',
        'user_id' => 'integer',
        'basic_salary' => 'double',
        'allowance' => 'double',
        'additional_earning' => 'double',
        'deduction' => 'double',
        'benefit' => 'double',
        'tax' => 'double',
    ];

    protected $appends = [
        'thp'
    ];

    public function getThpAttribute()
    {
        return $this->basic_salary + $this->allowance + $this->additional_earning + $this->benefit - $this->deduction - $this->tax;
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
