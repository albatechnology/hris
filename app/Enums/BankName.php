<?php

namespace App\Enums;

enum BankName: string
{
    use BaseEnum;

    case OCBC = 'OCBC';
    case BCA = 'BCA';
    case MANDIRI = 'MANDIRI';
    case BRI = 'BRI';
    case BNI = 'BNI';
}
