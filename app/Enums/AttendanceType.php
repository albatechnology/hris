<?php

namespace App\Enums;

enum AttendanceType: string
{
    use BaseEnum;

    case AUTOMATIC = 'automatic';
    case MANUAL = 'manual';
    case OTHER = 'other';
}
