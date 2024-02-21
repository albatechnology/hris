<?php

namespace App\Observers;

use App\Enums\TimeoffRenewType;
use App\Models\Company;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        if (!$company->timeoffRegulation) {
            $company->timeoffRegulation()->create([
                'renew_type' => TimeoffRenewType::ANNUAL,
                'total_day' => 12,
                'start_period' => date('m-d', strtotime(date('Y-01-01'))),
                'end_period' => date('m-d', strtotime(date('Y-12-31'))),
                'max_consecutively_day' => 5,
                'halfday_not_applicable_in' => ['Saturday', 'Sunday'],
                'is_expired_in_end_period' => true,
                'expired_max_month' => 3,
                'min_working_month' => 3,
                'cut_off_date' => '20',
                'min_advance_leave_working_month' => 5,
                'max_advance_leave_request' => 3,
                'dayoff_consecutively_working_day' => 15,
                'dayoff_consecutively_amount' => 1,
            ]);
        }
    }

    /**
     * Handle the Company "updated" event.
     */
    // public function updated(Company $company): void
    // {
    //     //
    // }

    /**
     * Handle the Company "deleted" event.
     */
    // public function deleted(Company $company): void
    // {
    //     //
    // }

    /**
     * Handle the Company "restored" event.
     */
    // public function restored(Company $company): void
    // {
    //     //
    // }

    /**
     * Handle the Company "force deleted" event.
     */
    // public function forceDeleted(Company $company): void
    // {
    //     //
    // }
}
