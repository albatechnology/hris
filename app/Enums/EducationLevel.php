<?php

namespace App\Enums;

enum EducationLevel: string
{
    use BaseEnum;

    case SD = 'SD';
    case SMP = 'SMP';
    case SMA = 'SMA';
    case D1 = 'D1';
    case D2 = 'D2';
    case D3 = 'D3';
    case D4 = 'D4';
    case SI = 'SI';
    case S2 = 'S2';
    case S3 = 'S3';
}
