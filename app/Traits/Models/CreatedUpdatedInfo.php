<?php

namespace App\Traits\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait CreatedUpdatedInfo
{
    public static function bootCreatedUpdatedInfo()
    {
        static::creating(function (self $model) {
            $model->created_by_id = auth('api')->id();
        });

        static::updating(function (self $model) {
            $model->updated_by_id = auth('api')->id();
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
