<?php

namespace App\Enums;

enum CostCenterCategory: string
{
    use BaseEnum;

    case DIRECT = 'direct';
    case IN_DIRECT = 'in_direct';
}
