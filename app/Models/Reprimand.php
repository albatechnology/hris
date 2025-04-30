<?php

namespace App\Models;

use App\Enums\MediaCollection;
use App\Enums\NotificationType;
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
        'status',
        'file'
    ];

    protected function status(): Attribute
    {
        return new Attribute(
            get: function () {
                return date('Y-m-d') >= $this->effective_date && date('Y-m-d') <= $this->end_date ? 'active' : 'inactive';
            },
        );
    }

    public function getFileAttribute()
    {
        $file = $this->getFirstMedia(MediaCollection::REPRIMAND->value);
        if ($file) {
            $url = $file->getUrl();
            // $preview = $file->getUrl('preview');
        } else {
            $url = null;
            // $preview = asset('img/user-icon.png');
        }

        return [
            'url' => $url,
            // 'preview' => $preview
        ];
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reprimand_watchers');
    }
}
