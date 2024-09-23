<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    use BaseEnum;

    case PERMANENT = 'permanent';
    case CONTRACT = 'contract';
    case PROBATION = 'probation';
    case FREELANCE = 'freelance';
    case INTERNSHIP = 'internship';
    case PART_TIME = 'part_time';
}
