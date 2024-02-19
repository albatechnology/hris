<?php

namespace App\Enums;

enum ClothesSize: string
{
    use BaseEnum;

    case S = 'S';
    case M = 'M';
    case L = 'L';
    case XL = 'XL';
    case XXL = 'XXL';
    case XXXL = 'XXXL';
}
