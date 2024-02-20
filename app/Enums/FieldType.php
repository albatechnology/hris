<?php

namespace App\Enums;

enum FieldType: string
{
    use BaseEnum;

    case TEXT = 'text';
    case DATE = 'date';
    case SELECT = 'select';

    public function getValidationRules(): string
    {
        return match ($this) {
            self::DATE => 'required|date',
            default => 'required|string',
        };
    }
}
