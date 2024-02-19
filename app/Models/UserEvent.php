<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEvent extends BaseModel
{
    public $incrementing = false;
    protected $fillable = ['user_id', 'event_id'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_at = now();
            $model->updated_at = now();
        });

        static::updating(function (self $model) {
            $model->updated_at = now();
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
