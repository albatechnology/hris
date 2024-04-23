<?php

namespace App\Models;

use App\Enums\RequestChangeDataType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RequestChangeDataDetail extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'type',
        'value',
    ];

    protected $casts = [
        'type' => RequestChangeDataType::class,
    ];

    protected $appends = [
        'old_value'
    ];

    public function getOldValueAttribute()
    {
        $this->load(['requestChangeData' => fn ($q) => $q->select('id', 'user_id')->with('user', fn ($q) => $q->select('id'))]);
        $this->requestChangeData->user->setAppends([]);
        return $this->type->getValue($this->requestChangeData->user->id);
    }

    public function requestChangeData(): BelongsTo
    {
        return $this->belongsTo(RequestChangeData::class);
    }
}
