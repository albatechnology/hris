<?php

namespace App\Enums;

enum MediaCollection: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case USER = 'user';
    case USER_EDUCATION = 'user_education';
    case ATTENDANCE = 'attendance';
    case TASK = 'task';
    case REQUEST_CHANGE_DATA = 'request_change_data';
    case USER_TRANSFER = 'user_transfer';
}
