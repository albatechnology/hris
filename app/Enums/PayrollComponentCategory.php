<?php

namespace App\Enums;

enum PayrollComponentCategory: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case BPJS = 'bpjs';
}
