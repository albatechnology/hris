<?php

namespace App\Enums;

enum ContactType: string
{
    use BaseEnum;

    case FAMILY = 'family';
    case EMERGENCY = 'emergency';
}
