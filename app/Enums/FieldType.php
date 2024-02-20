<?php

namespace App\Enums;

enum FieldType: string
{
    use BaseEnum;

    case TEXT = 'text';
    case DATE = 'date';
    case SELECT = 'select';
}
