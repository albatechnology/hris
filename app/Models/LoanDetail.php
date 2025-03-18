<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanDetail extends BaseModel
{
    protected $fillable = [
        'loan_id',
        'payment_period_year',
        'payment_period_month',
        'basic_payment',
        'interest',
    ];

    protected $appends = [
        'total',
        'remaining_loan',
    ];

    protected function total(): Attribute
    {
        return new Attribute(
            get: function () {
                return 0;
            },
        );
    }

    protected function remainingLoan(): Attribute
    {
        return new Attribute(
            get: function () {
                return 0;
            },
        );
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
