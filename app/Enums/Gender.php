<?php

namespace App\Enums;

enum Gender: string
{
    use BaseEnum;

    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';
}
