<?php

namespace App\Enums;

enum ProrateSetting: string
{
    use BaseEnum;

    case BASE_ON_WORKING_DAY = 'base_on_working_day';
    case BASE_ON_CALENDAR_DAY = 'base_on_calendar_day';
    case CUSTOM_ON_WORKING_DAY = 'custom_on_working_day';
    case CUSTOM_ON_CALENDAR_DAY = 'custom_on_calendar_day';

    public function hasProrateCustomWorkingDay(): bool
    {
        return match ($this) {
            self::CUSTOM_ON_WORKING_DAY, self::CUSTOM_ON_CALENDAR_DAY => true,
            default => false,
        };
    }

    public function hasCountNationalHolidayAsWorkingDay(): bool
    {
        return match ($this) {
            self::BASE_ON_WORKING_DAY, self::CUSTOM_ON_WORKING_DAY => true,
            default => false,
        };
    }
}
