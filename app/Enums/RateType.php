<?php

namespace App\Enums;

enum RateType: string
{
    use BaseEnum;

    case AMOUNT = 'amount';
    case SALARY = 'salary';
    case ALLOWANCES = 'allowances';
}
