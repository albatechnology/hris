<?php

namespace App\Observers;

use App\Models\Loan;

class LoanObserver
{
    /**
     * Handle the Loan "creating" event.
     */
    public function creating(Loan $loan): void
    {
        $loan->code = $loan->generateCode();
    }
}
