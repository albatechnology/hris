<?php

namespace App\Traits\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

trait CustomSoftDeletes
{
    use SoftDeletes;

    public static function bootCustomSoftDeletes()
    {
        static::deleted(function (self $model) {
            if (!str_contains(request()->url(), 'force-delete')) {
                $model->deleted_by = auth('sanctum')->user()->id;
                $model->saveQuietly();
            }
        });

        static::restoring(function (self $model) {
            $model->deleted_by = null;
        });
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
