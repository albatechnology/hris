<?php

namespace App\Models;

class UserTimeoffRegulationMonth extends BaseModel
{
    protected $fillable = [
        'user_id',
        'month',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
