<?php

namespace App\Models;

class NationalHoliday extends BaseModel
{
    protected $fillable = [
        'name',
        'date',
    ];

    protected $casts = [
        'name' => 'string',
        'date' => 'date',
    ];
}
