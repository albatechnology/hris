<?php

namespace App\Enums;

enum TimeoffRenewType: string
{
    use BaseEnum;

    case ANNUAL = 'annual';
    case USER_PERIOD = 'user_period';
    case MONTHLY = 'monthly';
}
