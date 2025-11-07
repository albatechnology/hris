<?php

namespace App\Models;

use App\Enums\PayrollComponentCategory;
use App\Services\RunThrService;
use App\Traits\Models\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunThrUser extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'run_thr_id',
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
        'run_thr_id' => 'integer',
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
        'total_month',
        'thr_prorate',
        'base_salary_original',
        'total_beban_month',
        'total_tax_month',
        'tax_thr',
        'thp_thr',
    ];

    public function getBaseSalaryOriginalAttribute(): int
    {
        return (int) $this->components()
            ->whereHas('payrollComponent', fn($q) => $q->where('category', PayrollComponentCategory::BASIC_SALARY))
            ->sum('amount');
    }

    public function getTotalMonthAttribute(): int
    {
        $benefit = $this->components()->whereHas('payrollComponent', fn($q) => $q->whereIn('category', [PayrollComponentCategory::COMPANY_BPJS_KESEHATAN, PayrollComponentCategory::COMPANY_JKK, PayrollComponentCategory::COMPANY_JKM]))->sum('amount');
        return round($this->total_earning + $benefit);
    }

    public function getThrProrateAttribute(): int
    {
        $joinDate = Carbon::parse($this->user->join_date)->startOfDay();
        $thrDate  = Carbon::parse($this->runThr->thr_date)->startOfDay();

        $diffDays    = $joinDate->diffInDays($thrDate);
        $fullMonths  = intdiv($diffDays, 30);
        $thrMultiplier = $fullMonths >= 12 ? 1 : (($fullMonths + 1) / 12);

        // return (int) round($thrMultiplier * $this->basic_salary);
        return $thrMultiplier * $this->base_salary_original;
    }

    public function getTotalBebanMonthAttribute(): int
    {
        return round($this->total_month + $this->thr_prorate);
        // return round($this->total_month + $this->basic_salary);
    }

    public function getTotalTaxMonthAttribute(): int
    {
        $tax = RunThrService::calculateTax($this->user->payrollInfo->ptkp_status, $this->total_beban_month);
        return $this->total_beban_month * ($tax / 100);
    }

    public function getTaxThrAttribute(): int
    {
        return $this->total_tax_month - $this->tax;
    }

    public function getThpThrAttribute(): int
    {
        return $this->thr_prorate - $this->tax_thr;
    }

    public function getThpAttribute(): int
    {
        return round($this->total_earning - $this->total_deduction);
    }

    public function getTotalEarningAttribute(): int
    {
        // return round($this->basic_salary + $this->allowance + $this->additional_earning);
        return round($this->base_salary_original + $this->allowance + $this->additional_earning);
    }

    public function getTotalDeductionAttribute(): int
    {
        return round($this->deduction + $this->tax);
    }

    public function runThr(): BelongsTo
    {
        return $this->belongsTo(RunThr::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(RunThrUserComponent::class);
    }
}
