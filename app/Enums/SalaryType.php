<?php

namespace App\Enums;

enum SalaryType: string
{
    use BaseEnum;

    case MONTHLY = 'monthly';
    case DAILY = 'daily';
}
