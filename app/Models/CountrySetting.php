<?php

namespace App\Models;

use App\Enums\CountrySettingKey;

class CountrySetting extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'key' => CountrySettingKey::class,
    ];
}
