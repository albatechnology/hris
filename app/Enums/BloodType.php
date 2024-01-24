<?php

namespace App\Enums;

enum BloodType: string
{
    use BaseEnum;

    case A = 'A';
    case A_PLUS = 'A+';
    case A_MINUS = 'A-';
    case B = 'B';
    case B_PLUS = 'B+';
    case B_MINUS = 'B-';
    case AB = 'AB';
    case AB_PLUS = 'AB+';
    case AB_MINUS = 'AB-';
    case O = 'O';
    case O_PLUS = 'O+';
    case O_MINUS = 'O-';
}
