<?php

namespace App\Observers;

use App\Enums\BankName;
use App\Enums\EventType;
use App\Enums\TimeoffPolicyType;
use App\Models\Company;
use App\Models\Overtime;
use App\Models\Role;
use App\Services\EventService;
use App\Services\SettingService;
use FontLib\Table\Type\name;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        $company->absenceReminder()->create();
        $company->banks()->create([
            'name' => BankName::BCA,
            'account_no' => '0000000000',
            'account_holder' => $company->name,
            'code' => '0000000000',
            'branch' => $company->city,
        ]);

        $division = $company->divisions()->create([
            'name' => 'Operational',
        ]);
        $division = $division->departments()->create([
            'name' => 'HR'
        ]);
        $company->positions()->create([
            'name' => 'Manager'
        ]);

        $dates = collect(EventService::getCalendarDate())->map(function ($date) {
            $date['start_at'] = $date['date'];
            $date['type'] = EventType::NATIONAL_HOLIDAY->value;
            unset($date['date']);
            return $date;
        });
        $company->events()->createMany($dates);

        $liveAttendance = $company->liveAttendances()->create([
            'name' => 'Live Attendance ' . $company->name,
            'is_flexible' => true,
        ]);
        $liveAttendance->locations()->create([
            'name' => 'Head Office',
            'radius' => 100,
            'lat' => "",
            'lng' => "",
        ]);

        $company->overtimes()->create([
            'compensation_rate_per_day' => 0,
            'name' => "Default",
            'rate_amount' => 0,
            'rate_type' => "amount",
        ]);

        $company->createPayrollSetting();

        // Role::create([
        //     'group_id' => $company->group_id,
        //     'name' => 'User' . $company->group_id,
        //     'guard_name' => 'web',
        // ]);

        $schedule = $company->schedules()->create([
            'name' => 'Default Schedule',
            'effective_date' => "2025-01-06",
            'description' => "Default Schedule",
        ]);
        $shift = $company->shifts()->create([
            'name' => 'Default Shift',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);
        for ($i = 1; $i < 8; $i++) {
            if ($i < 6) {
                $schedule->shifts()->attach($shift->id, ['order' => $i]);
            } else {
                $schedule->shifts()->attach(1, ['order' => $i]);
            }
        }

        // TimeoffRegulationService::create($company, TimeoffRenewType::PERIOD);
        \App\Models\RequestChangeDataAllowes::createForCompany($company);

        SettingService::create($company);

        $company->timeoffPolicies()->create([
            'name' => 'Annual Leave',
            'code' => 'AL',
            'type' => TimeoffPolicyType::ANNUAL_LEAVE,
            'effective_date' => date('Y-m-d'),
            'is_allow_halfday' => true,
            'max_consecutively_day' => 5,
        ]);
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
