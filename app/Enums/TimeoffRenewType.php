<?php

namespace App\Enums;

enum TimeoffRenewType: string
{
    use BaseEnum;

    case PERIOD = 'period';
    case USER_PERIOD = 'user_period';
    case MONTHLY = 'monthly';
}
