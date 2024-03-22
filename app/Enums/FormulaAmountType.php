<?php

namespace App\Enums;

enum FormulaAmountType: string
{
    use BaseEnum;

    case SALARY_PER_SCHEDULE_CALENDAR_DAY = 'salary_per_schedule_calendar_day';
    case FULL_SALARY = 'full_salary';
    case HALF_OF_SALARY = 'half_of_salary';
    case NUMBER = 'number';

    public static function getAvailableAmounts(): array
    {
        $data = self::all();
        unset($data[self::NUMBER->value]);
        return $data;
    }
}
