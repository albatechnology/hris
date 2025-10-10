<?php

namespace App\Enums;

enum ReimbursementPeriodType: string
{
    use BaseEnum;

    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
}
