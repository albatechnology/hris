<?php

namespace App\Enums;

enum OvertimeRequestType: string
{
    use BaseEnum;

    case OVERTIME = 'overtime';
    case TASK = 'task';
}
