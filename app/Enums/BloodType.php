<?php

namespace App\Enums;

enum BloodType: string
{
    use BaseEnum;

    case A = 'a';
    case B = 'b';
    case AB = 'ab';
    case O = 'o';
}
