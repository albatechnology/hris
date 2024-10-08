<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;

class UserTimeoffHistory extends BaseModel
{
    use BelongsToUser;

    const DESCRIPTION = [
        'USER_CREATED' => 'User created',
        'PERIOD_EXPIRED' => 'Timeoff expired',
        'PERIOD_RENEWED' => 'Timeoff renewed in new period',
        'ADD_TOTAL_REMAINING_TIMEOFF' => 'Add remaining timeoff',
        'DISCIPLINE_REWARD' => 'Discipline reward',
        'ADJUST' => 'Timeoff adjusted',
        'TIMEOFF' => 'Timeoff: %s',
        'ADVANCED_LEAVE' => 'Advanced leave',
    ];

    protected $fillable = [
        'user_id',
        'is_for_total_timeoff',
        'is_increment',
        'value',
        'description',
        'properties',
        'created_by',
    ];

    protected $casts = [
        'is_for_total_timeoff' => 'boolean',
        'is_increment' => 'boolean',
        'properties' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!isset($model->is_for_total_timeoff)) {
                $model->is_for_total_timeoff = true;
            }

            if (empty($model->created_by)) {
                $model->created_by = auth('sanctum')->user()?->id ?? null;
            }
        });

        static::created(function (self $model) {
            $column = $model->is_for_total_timeoff ? 'total_timeoff' : 'total_remaining_timeoff';

            if ($model->is_increment) {
                $model->user->increment($column, $model->value);
            } else {
                $model->user->decrement($column, $model->value);
            }
        });
    }
}
