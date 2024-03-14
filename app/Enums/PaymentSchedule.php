<?php

namespace App\Enums;

enum PaymentSchedule: string
{
    use BaseEnum;

    case DEFAULT = 'default';
}
