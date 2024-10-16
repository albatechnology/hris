<?php

namespace App\Enums;

enum PaidBy: string
{
    use BaseEnum;

    case COMPANY = 'company';
    case EMPLOYEE = 'employee';
}
