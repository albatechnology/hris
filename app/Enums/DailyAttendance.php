<?php

namespace App\Enums;

enum DailyAttendance: string
{
    use BaseEnum;

    case PRESENT = 'present';
    case ALPHA = 'alpha';
}
