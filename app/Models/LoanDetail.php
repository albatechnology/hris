<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanDetail extends BaseModel
{
    protected $fillable = [
        'run_payroll_user_id',
        'loan_id',
        'payment_period_year',
        'payment_period_month',
        'basic_payment',
        'interest',
    ];

    protected $appends = [
        'total',
    ];

    protected function total(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->basic_payment + ($this->basic_payment * ($this->interest / 100));
            },
        );
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function runPayrollUser()
    {
        return $this->belongsTo(RunPayrollUser::class);
    }
}
