<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Incident extends BaseModel implements HasMedia
{
    use CustomSoftDeletes, BelongsToUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'client_location_id',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->user_id)) {
                $model->user_id = auth('sanctum')->id();
            }
        });
    }

    public function clientLocation(): BelongsTo
    {
        return $this->belongsTo(ClientLocation::class);
    }
}
