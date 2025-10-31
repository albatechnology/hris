<?php

namespace App\Enums;

enum EventType: string
{
    use BaseEnum;

        // case EVENT = 'event';
    case COMPANY_HOLIDAY = 'company_holiday';
    case NATIONAL_HOLIDAY = 'national_holiday';
}
