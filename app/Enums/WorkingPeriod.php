<?php

namespace App\Enums;

enum WorkingPeriod: string
{
    use BaseEnum;

    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
}
