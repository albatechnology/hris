<?php

namespace App\Enums;

enum FormulaComponentEnum: string
{
    use BaseEnum;

    case JOB_LEVEL = 'job_level';
    case ELSE = 'else';
    case FUNCTION = 'function';
    case BRANCH = 'branch';
    case ORGANIZATION = 'organization';
    case JOB_POSITION = 'job_position';
    case EMPLOYEMENT_STATUS = 'employement_status';
    case EMPLOYEMENT_ID = 'employement_id';
    case GENDER = 'gender';
    case TIME_OFF_CODE = 'time_off_code';
    case ATTENDANCE_DAILY = 'attendance_daily';
    case SHIFT = 'shift';
    case MARITAL_STATUS = 'marital_status';
}
