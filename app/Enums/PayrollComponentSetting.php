<?php

namespace App\Enums;

enum PayrollComponentSetting: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case INCLUDE_BACKPAY = 'include_backpay';
    case FULL_SALARY = 'full_salary';
    case PRORATE_SALARY = 'prorate_salary';
}
