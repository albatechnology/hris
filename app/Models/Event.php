<?php

namespace App\Models;

use App\Enums\EventType;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'start_at',
        'end_at',
        'is_public',
        'is_send_email',
        'description',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'type' => EventType::class,
        'name' => 'string',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_public' => 'boolean',
        'is_send_email' => 'boolean',
        'description' => 'string',
    ];
}
