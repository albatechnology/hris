<?php

namespace App\Models;

use App\Enums\TokenType;
use App\Traits\Models\BelongsToUser;

class Token extends BaseModel
{
    use BelongsToUser;
    
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
