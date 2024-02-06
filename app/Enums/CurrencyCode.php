<?php

namespace App\Enums;

enum CurrencyCode: string
{
    use BaseEnum;

    case IDR = 'IDR';
    case MYR = 'MYR';
    case USD = 'USD';
}
