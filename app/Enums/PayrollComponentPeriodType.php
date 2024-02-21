<?php

namespace App\Enums;

enum PayrollComponentPeriodType: string
{
    use BaseEnum;

    case MONTHLY = 'monthly';
    case DAILY = 'daily';
    case ONE_TIME = 'one_time';
}
