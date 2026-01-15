<?php

namespace App\Observers;

use App\Models\RunPayroll;

class RunPayrollObserver
{
    /**
     * Handle the RunPayroll "creating" event.
     */
    public function creating(RunPayroll $runPayroll): void
    {
        // $runPayroll->code = $runPayroll->generateCode();
    }
}
