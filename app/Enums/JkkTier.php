<?php

namespace App\Enums;

enum JkkTier: int
{
    use BaseEnum;

    case VERY_LOW = 24;
    case LOW = 54;
    case MEDIUM = 89;
    case HIGH = 27;
    case VERY_HIGH = 74;

    public function getValue(): float
    {
        return floatval($this->value / 100);
    }
}
