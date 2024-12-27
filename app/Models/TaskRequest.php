<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TaskRequest extends RequestedBaseModel implements TenantedInterface, HasMedia
{
    use BelongsToUser, InteractsWithMedia, TenantedThroughUser;

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

    // protected static function booted(): void
    // {
    //     parent::booted();
    //     // static::creating(function (self $model) {
    //     //     $model->approved_by = $model->user->approval?->id ?? null;
    //     // });
    // }

    public function taskHour(): BelongsTo
    {
        return $this->belongsTo(TaskHour::class);
    }

    // public function approvedBy(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }

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
}
