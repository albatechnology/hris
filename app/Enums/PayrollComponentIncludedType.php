<?php

namespace App\Enums;

enum PayrollComponentIncludedType: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case OTHER = 'other';
}
