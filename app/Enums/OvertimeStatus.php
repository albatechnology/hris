<?php

namespace App\Enums;

enum OvertimeStatus: string
{
    use BaseEnum;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
