<?php

namespace App\Enums;

enum DefaultEmployeeTaxSetting: string
{
    use BaseEnum;

    case GROSS = 'gross';
    case GROSS_UP = 'gross_up';
    case NETTO = 'netto';
}
