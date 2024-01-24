<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\JobLevel;
use App\Traits\BelongsToUser;

class UserDetail extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'no_ktp',
        'kk_no',
        'job_position',
        'job_level',
        'employment_status',
        'join_date',
        'sign_date',
        'passport_no',
        'passport_expired',
        'address',
    ];

    protected $casts = [
        'employment_status' => EmploymentStatus::class,
        'job_level' => JobLevel::class,
    ];
}
