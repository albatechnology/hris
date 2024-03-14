<?php

namespace App\Enums;

enum OvertimeSetting: string
{
    use BaseEnum;

    case ELIGIBLE = 'eligible';
    case NOT_ELIGIBLE = 'not_eligible';
}
