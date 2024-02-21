<?php

namespace App\Models;

use App\Enums\SettingKey;
use App\Enums\SettingValueType;

// use Illuminate\Database\Eloquent\Casts\Attribute;

class Setting extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'key' => SettingKey::class,
        'value_type' => SettingValueType::class,
    ];

    // protected function value() : Attribute
    // {
    //     return Attribute::make(
    //         set: function (string $value) {
    //             return \App\Services\SettingService::set($this, $value)
    //         },
    //     );
    // }
}
