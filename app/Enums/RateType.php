<?php

namespace App\Enums;

enum RateType: string
{
    use BaseEnum;

    case AMOUNT = 'amount';
    case BASIC_SALARY = 'basic_salary';
    case ALLOWANCES = 'allowances';
    case FORMULA = 'formula';
}
