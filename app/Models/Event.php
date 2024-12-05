<?php

namespace App\Models;

use App\Enums\EventType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model implements TenantedInterface
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
        'type' => EventType::class,
        'is_public' => 'boolean',
        'is_send_email' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->type->is(EventType::NATIONAL_HOLIDAY)) {
                // $model->company_id = null;
                $model->start_at = date('Y-m-d H:i:s', strtotime($model->start_at));
                $model->end_at = $model->start_at;
                $model->is_public = true;
                $model->is_send_email = false;
            }
        });
    }

    public function userEvents(): HasMany
    {
        return $this->hasMany(UserEvent::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_events', 'event_id', 'user_id');
    }

    public function scopeWhereHoliday(Builder $query)
    {
        return $query->where('type', EventType::HOLIDAY);
    }

    public function scopeWhereEvent(Builder $query)
    {
        return $query->where('type', EventType::EVENT);
    }

    public function scopeWhereNationalHoliday(Builder $query)
    {
        return $query->where('type', EventType::NATIONAL_HOLIDAY);
    }
}
