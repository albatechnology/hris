<?php

namespace App\Models;

use App\Enums\PanicStatus;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\TenantedThroughClient;

class Panic extends BaseModel implements TenantedInterface
{
    use TenantedThroughClient, BelongsToUser;

    protected $fillable = [
        'client_id',
        'user_id',
        'lat',
        'lng',
        'status',
    ];

    protected $casts = [
        'status' => PanicStatus::class,
    ];
}
