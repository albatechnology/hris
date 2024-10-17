<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    use BaseEnum;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    case ON_PROGRESS = 'on_progress'; // only used for models extended to \App\Models\RequestedBaseModel
}
