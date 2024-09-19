<?php

namespace App\Enums;

enum ScheduleType: string
{
    use BaseEnum;

    case ATTENDANCE = 'attendance';
    case PATROL = 'patrol';
}
