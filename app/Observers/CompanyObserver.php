<?php

namespace App\Observers;

use App\Enums\TimeoffRenewType;
use App\Models\Company;
use App\Models\Overtime;
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
        Overtime::create([
            'company_id' => $company->id,
            'compensation_rate_per_day' => 10000,
            'is_rounding' => false,
            'name' => "Default Overtime",
            'rate_amount' => 10000,
            'rate_type' => "amount",
        ]);

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
