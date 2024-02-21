<?php

namespace App\Enums;

enum PayrollComponentDailyMaximumAmountType: string
{
    use BaseEnum;

    case NOT_USE = 'not_use';
    case BASIC_SALARY_PERCENTAGE = 'basic_salary_percentage';
    case CUSTOM_AMOUNT = 'custom_amount';
}
