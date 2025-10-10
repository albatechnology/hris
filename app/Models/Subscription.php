<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends BaseModel
{
    use CreatedUpdatedInfo, CustomSoftDeletes, BelongsToUser;

    protected $fillable = [
        'user_id',
        'group_id',
        'active_end_date',
        'max_users',
        'max_companies',
        'price',
        'discount',
        'total_price',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->total_price = max($model->price - $model->discount, 0);
        });
    }
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
