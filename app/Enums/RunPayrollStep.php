<?php

namespace App\Enums;

enum RunPayrollStep: int
{
    use BaseEnum;

    case ONE = 1;
    case TWO = 2;
    case THREE = 3;
}
