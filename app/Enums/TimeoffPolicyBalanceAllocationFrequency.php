<?php

namespace App\Enums;

enum TimeoffPolicyBalanceAllocationFrequency: string
{
    use BaseEnum;

    case ANNUALY = 'annualy';
    case ANNIVERSARY = 'anniversary';
}
