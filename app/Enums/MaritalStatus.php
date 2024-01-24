<?php

namespace App\Enums;

enum MaritalStatus: string
{
    use BaseEnum;

    case SINGLE = 'single';
    case MARRIED = 'married';
    case WIDOWED = 'widowed';
    case DIVORCED = 'divorced';
}
