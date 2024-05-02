<?php

namespace App\Enums;

enum RunPayrollStatus: int
{
    use BaseEnum;

    case REVIEW = 'review';
    case FINISH = 'finish';
}
