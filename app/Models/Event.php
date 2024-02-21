<?php

namespace App\Models;

use App\Enums\EventType;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function userEvents(): HasMany
    {
        return $this->hasMany(UserEvent::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_events', 'event_id', 'user_id');
    }
}
