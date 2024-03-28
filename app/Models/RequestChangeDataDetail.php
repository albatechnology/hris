<?php

namespace App\Models;

use App\Enums\RequestChangeDataType;

class RequestChangeDataDetail extends BaseModel
{
    protected $fillable = [
        'type',
        'value',
    ];

    protected $casts = [
        'type' => RequestChangeDataType::class,
    ];
}
