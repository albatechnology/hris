<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    use BaseEnum;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
