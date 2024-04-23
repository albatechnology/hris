<?php

namespace App\Enums;

enum BloodType: string
{
    use BaseEnum;

    case A = 'A';
    case B = 'B';
    case AB = 'AB';
    case O = 'O';
}
