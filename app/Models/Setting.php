<?php

namespace App\Models;

use App\Enums\SettingKey;
use App\Enums\SettingValueType;
use App\Traits\Models\CompanyTenanted;

class Setting extends BaseModel
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'key',
        'value',
        'value_type',
    ];

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
