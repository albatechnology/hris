<?php

namespace App\Observers;

use App\Models\RunThr;

class RunThrObserver
{
    /**
     * Handle the RunThr "creating" event.
     */
    public function creating(RunThr $runThr): void
    {
        $runThr->code = $runThr->generateCode();
    }
}
