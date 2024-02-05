<?php

namespace App\Enums;

enum TimeoffPolicyType: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case FREE_LEAVE = 'free_leave';
    case DAY_OFF = 'day_off';
}
