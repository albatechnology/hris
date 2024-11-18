<?php

namespace App\Traits\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

trait CreatedUpdatedInfo
{
    use SoftDeletes;

    public static function bootCreatedUpdatedInfo()
    {
        static::creating(function (self $model) {
            $model->created_by = auth('sanctum')->id();
        });

        static::updating(function (self $model) {
            $model->updated_by = auth('sanctum')->id();
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
