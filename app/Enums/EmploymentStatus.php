<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    use BaseEnum;

    case PERMANENT = 'permanent';
    case CONTRACT = 'contract';
    case FREELANCE = 'freelance';
    case VOLUNTEER = 'volunteer';
}
