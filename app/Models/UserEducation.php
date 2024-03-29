<?php

namespace App\Models;

use App\Enums\EducationLevel;
use App\Enums\EducationType;
use App\Traits\Models\BelongsToUser;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class UserEducation extends BaseModel implements HasMedia
{
    use BelongsToUser, InteractsWithMedia;

    public $table = 'user_educations';

    protected $fillable = [
        'user_id',
        'type',
        'level',
        'name',
        'institution_name',
        'majors',
        'start_date',
        'end_date',
        'expired_date',
        'score',
        'fee',
    ];

    protected $casts = [
        'type' => EducationType::class,
        'level' => EducationLevel::class,
        'fee' => 'double',
    ];
}
