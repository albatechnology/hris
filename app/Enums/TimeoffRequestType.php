<?php

namespace App\Enums;

enum TimeoffRequestType: string
{
    use BaseEnum;

    case FULL_DAY = 'full_day';
    case HALF_DAY = 'half_day';
}
