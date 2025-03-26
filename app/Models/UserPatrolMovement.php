<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPatrolMovement extends Model
{
    protected $fillable = [
        'user_patrol_batch_id',
        'datetime',
        'lat',
        'lng',
        'description',
    ];

    // protected static function booted(): void
    // {
    //     static::creating(function (self $model) {
    //         if (empty($model->datetime)) {
    //             $model->datetime = now();
    //         }
    //     });
    // }

    public function userPatrolBatch(): BelongsTo
    {
        return $this->belongsTo(UserPatrolBatch::class);
    }
}
