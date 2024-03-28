<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RequestChangeData extends BaseModel implements HasMedia
{
    use BelongsToUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'description',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(RequestChangeDataDetail::class);
    }
}
