<?php

namespace App\Enums;

enum Gender: string
{
    use BaseEnum;

    case MALE = 'male';
    case FEMALE = 'female';

    public function getTitle(): string
    {
        return match ($this) {
            self::MALE => "Mr. ",
            default => "Ms. ",
        };
    }
}
