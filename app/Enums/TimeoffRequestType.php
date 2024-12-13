<?php

namespace App\Enums;

enum TimeoffRequestType: string
{
    use BaseEnum;

    case FULL_DAY = 'full_day';
    case HALF_DAY_BEFORE_BREAK = 'half_day_before_break';
    case HALF_DAY_AFTER_BREAK = 'half_day_after_break';

    public function isHalfDay(): bool
    {
        return $this === self::HALF_DAY_BEFORE_BREAK || $this === self::HALF_DAY_AFTER_BREAK;
    }
}
