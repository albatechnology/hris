<?php

namespace App\Enums;

enum TimeoffPolicyType: string
{
    use BaseEnum;

    case TIME_OFF = 'time_off';
    case FREE_LEAVE = 'free_leave';
    case DAY_OFF = 'day_off';
}
