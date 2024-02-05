<?php

namespace App\Enums;

enum TimeoffRenewType: string
{
    use BaseEnum;

    case ANNUAL = 'annual';
    case PERIOD = 'period';
    case MONTHLY = 'monthly';
}
