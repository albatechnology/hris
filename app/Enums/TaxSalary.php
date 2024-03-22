<?php

namespace App\Enums;

enum TaxSalary: string
{
    use BaseEnum;

    case TAXABLE = 'taxable';
    case NON_TAXABLE = 'non_taxable';
}
