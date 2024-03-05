<?php

namespace App\Enums;

enum ImportType: string
{
    use BaseEnum;

    case NATIONAL_HOLIDAY = 'national_holiday';

    public function getImporter(): string
    {
        return match ($this) {
            self::NATIONAL_HOLIDAY => \App\Imports\NationalHolidaysImport::class,
            default => null
        };
    }

    public function getExporter(): string
    {
        return match ($this) {
            self::NATIONAL_HOLIDAY => \App\Exports\NationalHolidaysExport::class,
            default => null
        };
    }

    public function getModel(): string
    {
        return match ($this) {
            self::NATIONAL_HOLIDAY => \App\Models\NationalHoliday::class,
            default => null
        };
    }
}
