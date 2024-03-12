<?php

namespace App\Observers;

use App\Models\OvertimeRequest;

class OvertimeRequestObserver
{
    /**
     * Handle the OvertimeRequest "created" event.
     */
    public function created(OvertimeRequest $overtimeRequest): void
    {
        //
    }

    /**
     * Handle the OvertimeRequest "updating" event.
     */
    public function updating(OvertimeRequest $overtimeRequest): void
    {
        if ($overtimeRequest->isDirty('is_approved')) {
            $overtimeRequest->approved_by = auth('sanctum')->id();
            $overtimeRequest->approved_at = now();
        }
    }

    /**
     * Handle the OvertimeRequest "updated" event.
     */
    public function updated(OvertimeRequest $overtimeRequest): void
    {
        //
    }

    /**
     * Handle the OvertimeRequest "deleted" event.
     */
    public function deleted(OvertimeRequest $overtimeRequest): void
    {
        //
    }

    /**
     * Handle the OvertimeRequest "restored" event.
     */
    public function restored(OvertimeRequest $overtimeRequest): void
    {
        //
    }

    /**
     * Handle the OvertimeRequest "force deleted" event.
     */
    public function forceDeleted(OvertimeRequest $overtimeRequest): void
    {
        //
    }
}
