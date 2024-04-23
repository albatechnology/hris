<?php

namespace App\Enums;

enum EventType: string
{
    use BaseEnum;

    case EVENT = 'event';
    case HOLIDAY = 'holiday';
    case NATIONAL_HOLIDAY = 'national_holiday';
}
