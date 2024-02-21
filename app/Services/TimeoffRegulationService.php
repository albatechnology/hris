<?php

namespace App\Services;

use App\Enums\TimeoffRenewType;
use App\Models\Company;
use App\Models\TimeoffPeriodRegulation;
use App\Models\TimeoffRegulation;

class TimeoffRegulationService
{
    public static function create(Company $company, string $renewType): TimeoffRegulation
    {
        $renewType = TimeoffRenewType::tryFrom($renewType);

        if ($renewType->is(TimeoffRenewType::USER_PERIOD)) {
            return self::createUserPeriod($company);
        } elseif ($renewType->is(TimeoffRenewType::MONTHLY)) {
            return self::createMonthly($company);
        } else {
            return self::createAnnual($company);
        }
    }

    public static function createUserPeriod(Company $company): TimeoffRegulation
    {
        return $company->timeoffRegulation()->create([
            'renew_type' => TimeoffRenewType::USER_PERIOD,
            'total_day' => 12,
            'start_period' => null,
            'end_period' => null,
            'max_consecutively_day' => 5,
            'halfday_not_applicable_in' => ['Saturday', 'Sunday'],
            'is_expired_in_end_period' => true,
            'expired_max_month' => null,
            'min_working_month' => 3,
            'cut_off_date' => '20',
            'min_advance_leave_working_month' => 5,
            'max_advance_leave_request' => 3,
            'dayoff_consecutively_working_day' => 15,
            'dayoff_consecutively_amount' => 1,
        ]);
    }

    public static function createMonthly(Company $company): TimeoffRegulation
    {
        /** @var TimeoffRegulation $timeoffRegulation */
        $timeoffRegulation = $company->timeoffRegulation()->create([
            'renew_type' => TimeoffRenewType::MONTHLY,
            'total_day' => 12,
            'start_period' => null,
            'end_period' => null,
            'max_consecutively_day' => 5,
            'halfday_not_applicable_in' => ['Saturday', 'Sunday'],
            'is_expired_in_end_period' => true,
            'expired_max_month' => null,
            'min_working_month' => 3,
            'cut_off_date' => '20',
            'min_advance_leave_working_month' => 5,
            'max_advance_leave_request' => 3,
            'dayoff_consecutively_working_day' => 15,
            'dayoff_consecutively_amount' => 1,
        ]);

        /** @var TimeoffPeriodRegulation $timeoffPeriodRegulation */
        $timeoffPeriodRegulation = $timeoffRegulation->timeoffPeriodRegulations()->create([
            'min_working_month' => 0,
            'max_working_month' => 12,
        ]);
        $timeoffPeriodRegulation->timeoffRegulationMonths()->createMany([
            ['month' => '01', 'amount' => 1],
            ['month' => '02', 'amount' => 1],
            ['month' => '03', 'amount' => 1],
            ['month' => '04', 'amount' => 1],
            ['month' => '05', 'amount' => 1],
            ['month' => '06', 'amount' => 1],
            ['month' => '07', 'amount' => 1],
            ['month' => '08', 'amount' => 1],
            ['month' => '09', 'amount' => 1],
            ['month' => '10', 'amount' => 1],
            ['month' => '11', 'amount' => 1],
            ['month' => '12', 'amount' => 1],
        ]);

        return $timeoffRegulation;
    }

    public static function createAnnual(Company $company)
    {
        dump('createAnnual');
        dd($company);
    }
}
