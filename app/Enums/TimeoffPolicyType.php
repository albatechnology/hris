<?php

namespace App\Enums;

enum TimeoffPolicyType: string
{
    use BaseEnum;

    case TIME_OFF = 'time_off';
    case SPECIAL_LEAVE = 'special_leave';
    case DAY_OFF = 'day_off';
}
