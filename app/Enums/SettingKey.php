<?php

namespace App\Enums;

enum SettingKey: string
{
    use EnumHelper;

    case LIVE_ATTENDANCE_MOBILE_SETUP = 'live_attendance_mobile_setup';

    // public function getDescription(): string
    // {
    //     return match ($this) {
    //         self::LIVE_ATTENDANCE_MOBILE_SETUP => 'live_attendance_mobile_setup',
    //         default => '',
    //     };
    // }
}
