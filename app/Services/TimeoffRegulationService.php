<?php

namespace App\Services;

use App\Enums\TimeoffRenewType;
use App\Models\Company;
use App\Models\TimeoffPeriodRegulation;
use App\Models\TimeoffRegulation;

class TimeoffRegulationService
{
    public static function createDefaultMonthlyPeriod(TimeoffRegulation $timeoffRegulation): void
    {
        /** @var TimeoffPeriodRegulation $timeoffPeriodRegulation */
        $timeoffPeriodRegulation = $timeoffRegulation->timeoffPeriodRegulations()->create([
            'min_working_month' => 0,
            'max_working_month' => 1000,
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
    }
    /**
     * Create a new timeoff regulation based on the given company and renew type.
     *
     * @param Company $company The company for which the timeoff regulation is created
     * @param TimeoffRenewType|string $renewType The type of renew for the timeoff regulation
     * @return TimeoffRegulation The created timeoff regulation
     */
    public static function create(Company $company, TimeoffRenewType|string $renewType): TimeoffRegulation
    {
        if (!($renewType instanceof TimeoffRenewType)) {
            $renewType = TimeoffRenewType::tryFrom($renewType);
        }

        if ($renewType->is(TimeoffRenewType::USER_PERIOD)) {
            return self::createUserPeriod($company);
        } elseif ($renewType->is(TimeoffRenewType::MONTHLY)) {
            return self::createMonthly($company);
        } else {
            return self::createPeriod($company);
        }
    }

    public static function createUserPeriod(Company $company): TimeoffRegulation
    {
        $timeoffRegulation = $company->timeoffRegulation()->create([
            'renew_type' => TimeoffRenewType::USER_PERIOD,
            'total_day' => 12,
            'start_period' => '01-01',
            'end_period' => '12-31',
            // 'max_consecutively_day' => 5,
            'halfday_not_applicable_in' => ['saturday', 'sunday'],
            'is_expired_in_end_period' => true,
            'expired_max_month' => null,
            'min_working_month' => 1,
            'cut_off_date' => '20',
            'min_advanced_leave_working_month' => 5,
            'max_advanced_leave_request' => 3,
            'dayoff_consecutively_working_day' => 15,
            'dayoff_consecutively_amount' => 1,
        ]);

        return $timeoffRegulation;
    }

    public static function createMonthly(Company $company): TimeoffRegulation
    {
        /** @var TimeoffRegulation $timeoffRegulation */
        $timeoffRegulation = $company->timeoffRegulation()->create([
            'renew_type' => TimeoffRenewType::MONTHLY,
            'total_day' => 12,
            'start_period' => '01-01',
            'end_period' => '12-31',
            // 'max_consecutively_day' => 5,
            'halfday_not_applicable_in' => ['saturday', 'sunday'],
            'is_expired_in_end_period' => true,
            'expired_max_month' => null,
            'min_working_month' => 3,
            'cut_off_date' => '20',
            'min_advanced_leave_working_month' => 5,
            'max_advanced_leave_request' => 3,
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

    public static function createPeriod(Company $company)
    {
        $timeoffRegulation = $company->timeoffRegulation()->create([
            'renew_type' => TimeoffRenewType::PERIOD,
            'total_day' => 12,
            'start_period' => '01-01',
            'end_period' => '12-31',
            // 'max_consecutively_day' => 5,
            'halfday_not_applicable_in' => ['saturday', 'sunday'],
            'is_expired_in_end_period' => true,
            'expired_max_month' => null,
            'min_working_month' => 1,
            'cut_off_date' => '20',
            'min_advanced_leave_working_month' => 5,
            'max_advanced_leave_request' => 3,
            'dayoff_consecutively_working_day' => 15,
            'dayoff_consecutively_amount' => 1,
        ]);

        return $timeoffRegulation;
    }

    /**
     * Check if the join date has passed a certain number of working months.
     *
     * This function compares the join date with the current date, and if the join date
     * has passed the minimum number of working months, it will return true. Otherwise,
     * it will return false.
     *
     * @param string $joinDate The join date to compare. Format: Y-m-d
     * @param int $minWorkingMonth The minimum number of working months to check against.
     * @return bool True if the join date has passed the minimum number of working months, false otherwise.
     */
    public static function isJoinDatePassed(string $joinDate, int $minWorkingMonth): bool
    {
        $minWorkingMonthDate = new \DateTime($joinDate);
        $minWorkingMonthDate->add(new \DateInterval(sprintf('P%sM', $minWorkingMonth)));
        if (date('Y-m-d') >= $minWorkingMonthDate->format('Y-m-d')) return true;
        return false;
    }

    /**
     * Update the end period of a TimeoffRegulation.
     *
     * This function will update the end period of a TimeoffRegulation by calculating
     * the difference between the start period and the end period, and then adding that
     * difference to the start period. The start period and end period will be updated
     * accordingly.
     *
     * @param TimeoffRegulation $timeoffRegulation The TimeoffRegulation to update
     */
    public static function updateEndPeriod(TimeoffRegulation $timeoffRegulation): void
    {
        if (empty($timeoffRegulation->start_period) || empty($timeoffRegulation->end_period)) {
            return;
        }

        $startAt = new \DateTime(date('Y-') . $timeoffRegulation->start_period);
        $endAt = new \DateTime(date('Y-') . $timeoffRegulation->end_period);

        $interval = $startAt->diff($endAt);

        $startAt->add($interval)->modify('+1 day');
        $endAt->add($interval);

        $timeoffRegulation->update([
            'start_period' => $startAt->format('m-d'),
            'end_period' => $endAt->format('m-d'),
        ]);
    }
}
