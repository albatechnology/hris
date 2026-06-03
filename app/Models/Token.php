<?php

namespace App\Models;

use App\Enums\TokenType;

class Token extends BaseModel
{
    protected $fillable = [
        'user_id',
        'type',
        'token',
        'device_name',
        'ip_address',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'type' => TokenType::class,
        'expires_at' => 'datetime',
    ];
}
