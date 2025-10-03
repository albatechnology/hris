<?php

namespace App\Models;

use App\Enums\BloodType;
use App\Enums\ClothesSize;
use App\Enums\EmploymentStatus;
use App\Enums\JobLevel;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Traits\Models\BelongsToUser;

class UserDetail extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'no_ktp',
        'kk_no',
        'postal_code',
        'address',
        'address_ktp',
        'job_position',
        'job_level',
        'employment_status',
        'passport_no',
        'passport_expired',
        'birth_place',
        'birthdate',
        'marital_status',
        'blood_type',
        'rhesus',
        'religion',
        'batik_size',
        'tshirt_size',
        'lat',
        'lng',
        'speed',
        'battery',
        'detected_at',
        'last_absence_reminder_at',
        'issue_date'
    ];

    protected $casts = [
        'no_ktp' => 'string',
        'kk_no' => 'string',
        'passport_no' => 'string',
        'employment_status' => EmploymentStatus::class,
        'job_level' => JobLevel::class,
        'blood_type' => BloodType::class,
        'religion' => Religion::class,
        'marital_status' => MaritalStatus::class,
        'batik_size' => ClothesSize::class,
        'tshirt_size' => ClothesSize::class,
    ];
}
