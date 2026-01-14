<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TaskRequest extends RequestedBaseModel implements TenantedInterface, HasMedia
{
    use InteractsWithMedia, TenantedThroughUser, CustomSoftDeletes;

    protected $fillable = [
        'user_id',
        'task_hour_id',
        'start_at',
        'end_at',
        'note',
        // 'approval_status',
        // 'approved_by',
        // 'approved_at',
    ];

    protected $appends = [
        'approval_status',
        'files'
    ];

    public function taskHour(): BelongsTo
    {
        return $this->belongsTo(TaskHour::class);
    }

    public function getFilesAttribute()
    {
        $files = $this->getMedia(\App\Enums\MediaCollection::TASK->value);
        $data = [];
        if ($files->count() > 0) {
            foreach ($files as $file) {
                $data[] = $file->getUrl();
            }
        }
        return $data;
    }

    public function scopeWhereDateBetween($query, string $startAt, string $endAt)
    {
        $query->whereDate('start_at', '>=', $startAt)->whereDate('end_at', '<=', $endAt);
    }
}
