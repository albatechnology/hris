<?php

namespace App\Models;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UserPatrolTask extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'user_patrol_batch_id',
        'patrol_task_id',
        'schedule_id',
        'shift_id',
        'description',
        'datetime',
        'lat',
        'lng',
    ];

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 150) // resize dan crop biar square
            ->quality(100) // jaga kualitas
            ->nonOptimized()
            ->queued();
    }
}
