<?php

namespace App\Enums;

enum FormulaComponentEnum: string
{
    use BaseEnum;

    case DAILY_ATTENDANCE = 'daily_attendance';
    case SHIFT = 'shift';
    case BRANCH = 'branch';
        // case HOLIDAY = 'holiday';
    case EMPLOYEMENT_STATUS = 'employement_status';
    case JOB_POSITION = 'job_position';
    case GENDER = 'gender';
    case RELIGION = 'religion';
    case MARITAL_STATUS = 'marital_status';
    case ELSE = 'else';

    // case JOB_LEVEL = 'job_level';
    // case FUNCTION = 'function';
    // case ORGANIZATION = 'organization';
    // case EMPLOYEMENT_ID = 'employement_id';
    // case TIME_OFF_CODE = 'time_off_code';

    public function getData(): array
    {
        return match ($this) {
            self::DAILY_ATTENDANCE => DailyAttendance::all(),
            // self::SHIFT => \App\Models\Shift::tenanted()->orWhereNull('company_id')->get(['id', 'name'])->pluck('name', 'id')->toArray(),
            self::SHIFT => [
                ...\App\Models\Shift::tenanted()->orWhereNull('company_id')->get(['id', 'name'])->pluck('name', 'id')->toArray(),
                'national_holiday' => 'national_holiday',
                // 'company_holiday' => 'company_holiday',
            ],
            self::BRANCH => \App\Models\Branch::tenanted()->get(['branches.id', 'branches.name'])->pluck('name', 'id')->toArray(),
            // self::HOLIDAY => [
            //     'event' => 'Event',
            //     'national_holiday' => 'National Holiday',
            //     'holiday' => 'Company Holiday',
            // ],
            self::EMPLOYEMENT_STATUS => EmploymentStatus::all(),
            self::JOB_POSITION => \App\Models\Position::tenanted()->get(['id', 'name'])->pluck('name', 'id')->toArray(),
            self::GENDER => Gender::all(),
            self::RELIGION => Religion::all(),
            self::MARITAL_STATUS => MaritalStatus::all(),
            self::ELSE => [
                'else' => 'else'
            ],
            default => []
        };
    }
}
