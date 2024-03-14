<?php

namespace App\Enums;

enum ProrateSetting: string
{
    use BaseEnum;

    case BASE_ON_WORKING_DAY = 'base_on_working_day';
    case BASE_ON_CALENDAR_DAY = 'base_on_calendar_day';
    case CUSTOM_ON_WORKING_DAY = 'custom_on_working_day';
    case CUSTOM_ON_CALENDAR_DAY = 'custom_on_calendar_day';
}
