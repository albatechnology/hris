<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class NationalHoliday extends BaseModel
{
    // use SoftDeletes;

    protected $fillable = [
        'name',
        'date',
    ];
}
