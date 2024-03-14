<?php

namespace App\Enums;

enum TaxMethod: string
{
    use BaseEnum;

    case GROSS = 'gross';
}
