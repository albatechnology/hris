<?php

namespace App\Enums;

enum Religion: string
{
    use BaseEnum;

    case ISLAM = 'islam';
    case KRISTEN = 'kristen';
    case KATOLIK = 'katolik';
    case BUDDHA = 'buddha';
    case HINDU = 'hindu';
    case KONGHUCU = 'konghucu';
    case OTHER = 'other';
}
