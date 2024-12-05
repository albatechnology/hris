<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\TenantedThroughUser;

class UserExperience extends BaseModel implements TenantedInterface
{
    use BelongsToUser, TenantedThroughUser;

    protected $fillable = [
        'user_id',
        'company',
        'department',
        'position',
        'start_date',
        'end_date',
    ];
}
