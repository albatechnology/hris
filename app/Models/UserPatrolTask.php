<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class UserPatrolTask extends BaseModel implements HasMedia, TenantedInterface
{
    use InteractsWithMedia;

    protected $fillable = [
        // 'user_id',
        'user_patrol_batch_id',
        'patrol_task_id',
        'schedule_id',
        'shift_id',
        'description',
        'datetime',
        'lat',
        'lng',
    ];

    // protected static function booted(): void
    // {
    //     static::creating(function (self $model) {
    //         if (empty($model->datetime)) {
    //             $model->datetime = now();
    //         }
    //     });
    // }

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        return $query->whereHas('userPatrolBatch', fn($q) => $q->tenanted());

        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        if ($user->is_super_admin) {
            return $query;
        }

        if ($user->is_administrator) {
            return $query->whereHas('userPatrolBatch', fn($q) => $q->whereHas('user', fn($q) => $q->where('group_id', $user->group_id)));
        }

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        return $query->whereHas('userPatrolBatch', fn($q) => $q->whereHas('user', fn($q) => $q->whereHas('companies', fn($q) => $q->whereIn('company_id', $companyIds))));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function patrolTask(): BelongsTo
    {
        return $this->belongsTo(PatrolTask::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function userPatrolBatch(): BelongsTo
    {
        return $this->belongsTo(UserPatrolBatch::class);
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(\Spatie\Image\Enums\Fit::Max, 160, 160)
            ->quality(100)
            ->nonOptimized()
            ->queued();

        $this->addMediaConversion('xls_thumb')     // kecil untuk export HTML-XLS
            ->format('jpg')
            ->fit(\Spatie\Image\Enums\Fit::Max, 96, 96)
            ->quality(30)
            ->nonOptimized()
            ->nonQueued();
    }

    public function getBase64Image($conversion = 'thumb')
    {
        if (!$this->media->count()) {
            return null;
        }

        $media = $this->media->first();
        $url = $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();

        try {
            // Download ke memory (bukan ke file)
            $imageData = file_get_contents($url);
            $base64 = base64_encode($imageData);
            $mimeType = finfo_buffer(finfo_open(), $imageData, FILEINFO_MIME_TYPE);

            return "data:{$mimeType};base64,{$base64}";
        } catch (\Exception $e) {
            Log::warning("Failed to get base64 image: " . $e->getMessage());
            return null;
        }
    }
}
