<?php

namespace App\Enums;

enum FieldType: string
{
    use BaseEnum;

    case TEXT = 'text';
    case DATE = 'date';
    case SELECT = 'select';
    case FILE = 'file';

    public function getValidationRules(): string
    {
        return match ($this) {
            self::DATE => 'required|date',
            self::FILE => 'required|mimes:' . config('app.file_mimes_types'),
            default => 'required|string',
        };
    }
}
