<?php

namespace App\Enums;

enum RunReprimandStatus: string
{
    use BaseEnum;

    case REVIEW = 'review';
    case RELEASE = 'release';
}
