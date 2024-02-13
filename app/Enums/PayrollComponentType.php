<?php

namespace App\Enums;

enum PayrollComponentType: string
{
    use BaseEnum;

    case ALLOWANCE = 'allowance';
    case DEDUCTION = 'deduction';
    case BENEFIT = 'benefit';
}
