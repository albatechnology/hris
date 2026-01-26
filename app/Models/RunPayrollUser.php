<?php

namespace App\Models;

use App\Enums\PayrollComponentType;
use App\Enums\TaxMethod;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunPayrollUser extends BaseModel
{
    use BelongsToUser;

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
        'total_benefit',
    ];

    public function getThpAttribute(): int
    {
        return round($this->total_earning - $this->total_deduction);
    }

    public function getTotalEarningAttribute(): int
    {
        return round($this->basic_salary + $this->allowance + $this->additional_earning);
    }

    public function getTotalDeductionAttribute(): int
    {
        if ($this->user->payrollInfo?->tax_method->is(TaxMethod::GROSS)) {
            return round($this->deduction + $this->tax);
        }

        return round($this->deduction);
    }

    public function getTotalBenefitAttribute(): int
    {
        return round($this->components()->whereHas('payrollComponent', fn($q) => $q->where('type', PayrollComponentType::BENEFIT))->sum('amount'));
    }

    public function runPayroll(): BelongsTo
    {
        return $this->belongsTo(RunPayroll::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(RunPayrollUserComponent::class);
    }
}
