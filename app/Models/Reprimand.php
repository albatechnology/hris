<?php

namespace App\Models;

use App\Enums\ReprimandType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Reprimand extends BaseModel implements TenantedInterface, HasMedia
{
    use BelongsToUser, CreatedUpdatedInfo, CustomSoftDeletes, TenantedThroughUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        // 'assign_to',
        'type',
        'effective_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'type' => ReprimandType::class,
    ];

    protected $appends = [
        'status'
    ];

    protected static function booted(): void
    {
        static::created(function (self $model) {
            if ($model->isDirty('approval_status')) {
                $model->approved_at = now();
            }
        });
    }

    protected function status(): Attribute
    {
        return new Attribute(
            get: function () {
                return date('Y-m-d') >= $this->effective_date && date('Y-m-d') <= $this->end_date ? 'active' : 'inactive';
            },
        );
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reprimand_watchers');
    }
}
