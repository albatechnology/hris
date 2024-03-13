<?php

namespace App\Enums;

enum MediaCollection: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case USER = 'user';
    case USER_EDUCATION = 'user_education';
    case ATTENDANCE = 'attendance';
}
