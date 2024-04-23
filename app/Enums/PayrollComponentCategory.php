<?php

namespace App\Enums;

enum PayrollComponentCategory: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case BASIC_SALARY = 'basic_salary';
    case BPJS = 'bpjs';
}
