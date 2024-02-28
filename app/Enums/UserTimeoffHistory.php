<?php

namespace App\Enums;

enum UserTimeoffHistory: string
{
    use BaseEnum;

    case USER_CREATED = 'user_created';
    case ADJUST = 'adjust';
    case TIMEOFF = 'timeoff';

    public static function getDescription(): array
    {
        return [
            self::USER_CREATED,
            self::ADJUST,
            self::TIMEOFF,
        ];
    }
}
