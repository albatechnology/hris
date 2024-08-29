<?php

namespace App\Models;

class Announcementable extends BaseModel
{
    protected $fillable = [
        'announcement_id',
        'announcementable_type',
        'announcementable_id',
    ];

    public $timestamps = false;
}
