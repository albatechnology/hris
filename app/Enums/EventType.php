<?php

namespace App\Enums;

enum EventType: string
{
    use BaseEnum;

    case EVENT = 'event';
    case HOLIDAY = 'holiday';
}
