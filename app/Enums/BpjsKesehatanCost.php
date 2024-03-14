<?php

namespace App\Enums;

enum BpjsKesehatanCost: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case NOT_PAID = 'not_paid';
    case PAID_BY_COMPANY = 'paid_by_company';
    case PAID_BY_EMPLOYEE = 'paid_by_employee';
}
