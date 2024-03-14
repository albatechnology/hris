<?php

namespace App\Enums;

enum TaxSalary: string
{
    use BaseEnum;

    case TAXABLE = 'taxable';
}
