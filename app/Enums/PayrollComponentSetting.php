<?php

namespace App\Enums;

enum PayrollComponentSetting: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case FULL_SALARY = 'full_salary';
    case PRORATE_SALARY = 'prorate_salary';
}
