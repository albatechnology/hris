<?php

namespace App\Models;

use App\Enums\ContactType;
use App\Enums\Gender;
use App\Enums\RelationshipType;
use App\Enums\Religion;
use App\Traits\BelongsToUser;

class UserContact extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'id_number',
        'relationship',
        'gender',
        'job',
        'religion',
        'birthdate',
        'email',
        'phone',
    ];

    protected $casts = [
        'type' => ContactType::class,
        'relationship' => RelationshipType::class,
        'gender' => Gender::class,
        'religion' => Religion::class,
    ];
}
