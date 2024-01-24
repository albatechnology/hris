<?php

namespace App\Models;

use App\Traits\BelongsToUser;

class UserExperience extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'company_name',
        'department',
        'position',
        'start_date',
        'end_date',
    ];
}
