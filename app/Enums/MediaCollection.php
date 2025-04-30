<?php

namespace App\Enums;

enum MediaCollection: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case USER = 'user';
    case USER_EDUCATION = 'user_education';
    case ATTENDANCE = 'attendance';
    case TIMEOFF = 'timeoff';
    case TASK = 'task';
    case REQUEST_CHANGE_DATA = 'request_change_data';
    case USER_TRANSFER = 'user_transfer';
    case QR_CODE = 'qr_code';
    case GUEST_BOOK_CHECK_IN = 'guest_book_check_in';
    case GUEST_BOOK_CHECK_OUT = 'guest_book_check_out';
    case REPRIMAND = 'reprimand';
}
