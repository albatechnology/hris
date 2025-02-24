<?php

namespace App\Enums;

enum TimeoffPolicyType: string
{
    use BaseEnum;

    case ANNUAL_LEAVE = 'annual_leave';
    case DAY_OFF = 'day_off';
    case EXTRA_OFF = 'extra_off';
    case SICK_WITHOUT_CERTIFICATE = 'sick_without_certificate';
    case SICK_WITH_CERTIFICATE = 'sick_with_certificate';
    case PERMISSION = 'permission';
    case FREE_LEAVE = 'free_leave';
    case UNPAID_LEAVE = 'unpaid_leave';
    case MATERNITY_LEAVE = 'maternity_leave';

    public static function hasQuotas(): array
    {
        return [self::ANNUAL_LEAVE, self::DAY_OFF, self::EXTRA_OFF, self::SICK_WITHOUT_CERTIFICATE];
    }

    public function hasQuota(): bool
    {
        return in_array($this, self::hasQuotas());
    }
}
