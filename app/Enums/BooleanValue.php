<?php

namespace App\Enums;

enum BooleanValue: int
{
    use BaseEnum;

    case TRUE = true || 1;
    case FALSE = false || 0;
}
