<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;

class UserSupervisor extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'supervisor_id',
        'order',
    ];
}
