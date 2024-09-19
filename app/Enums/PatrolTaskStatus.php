<?php

namespace App\Enums;

enum PatrolTaskStatus: string
{
    use BaseEnum;

    case PENDING = 'pending';
    case COMPLETE = 'complete';
    case CANCEL = 'cancel';
}
