<?php

namespace App\Enums;

enum TimeoffPolicyType: string
{
    use BaseEnum;

    case SICK_WITHOUT_CERTIFICATE = 'sick_without_certificate';
    case SICK_WITH_CERTIFICATE = 'sick_with_certificate';
    case DAY_OFF = 'day_off';
    case TIME_OFF = 'time_off';
    case PERMISSION = 'permission';
    case FREE_LEAVE = 'free_leave';
    case UNPAID_LEAVE = 'unpaid_leave';
    case MATERNITY_LEAVE = 'maternity_leave';

    public static function hasQuotas(): array
    {
        return [self::SICK_WITHOUT_CERTIFICATE, self::DAY_OFF, self::TIME_OFF];
    }

    public function hasQuota(): bool
    {
        return in_array($this, self::hasQuotas());
    }
}
