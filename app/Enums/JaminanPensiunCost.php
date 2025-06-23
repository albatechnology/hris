<?php

namespace App\Enums;

enum JaminanPensiunCost: string
{
    use BaseEnum;

    // case DEFAULT = 'default';
    case NOT_PAID = 'not_paid';
    case COMPANY = 'company';
    case EMPLOYEE = 'employee';
}
