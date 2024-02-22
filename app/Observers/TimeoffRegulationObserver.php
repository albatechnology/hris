<?php

namespace App\Observers;

use App\Models\TimeoffRegulation;

class TimeoffRegulationObserver
{
    /**
     * Handle the TimeoffRegulation "created" event.
     */
    public function saved(TimeoffRegulation $timeoffRegulation): void
    {
        // dd($timeoffRegulation);
    }

    /**
     * Handle the TimeoffRegulation "created" event.
     */
    // public function created(TimeoffRegulation $timeoffRegulation): void
    // {
    //     //
    // }

    /**
     * Handle the TimeoffRegulation "updated" event.
     */
    // public function updated(TimeoffRegulation $timeoffRegulation): void
    // {
    //     //
    // }

    /**
     * Handle the TimeoffRegulation "deleted" event.
     */
    // public function deleted(TimeoffRegulation $timeoffRegulation): void
    // {
    //     //
    // }

    /**
     * Handle the TimeoffRegulation "restored" event.
     */
    // public function restored(TimeoffRegulation $timeoffRegulation): void
    // {
    //     //
    // }

    /**
     * Handle the TimeoffRegulation "force deleted" event.
     */
    // public function forceDeleted(TimeoffRegulation $timeoffRegulation): void
    // {
    //     //
    // }
}
