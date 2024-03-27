<?php

namespace App\Enums;

enum UpdatePayrollComponentType: string
{
    use BaseEnum;

    case ADJUSTMENT = 'adjustment';
    case EXPIRED = 'expired';
}
