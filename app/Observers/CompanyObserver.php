<?php

namespace App\Observers;

use App\Enums\TimeoffRenewType;
use App\Models\Company;
use App\Services\SettingService;
use App\Services\TimeoffRegulationService;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        TimeoffRegulationService::create($company, TimeoffRenewType::PERIOD);
        $company->createPayrollSetting();
        \App\Models\RequestChangeDataAllowes::createForCompany($company);

        SettingService::create($company);
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
