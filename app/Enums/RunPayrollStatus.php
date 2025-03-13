<?php

namespace App\Enums;

enum RunPayrollStatus: string
{
    use BaseEnum;

    case REVIEW = 'review';
    case RELEASE = 'release';
}
