<?php

namespace App\Models;

use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;

class UserPatrolBatch extends BaseModel
{
    use TenantedThroughUser, CreatedUpdatedInfo, CustomSoftDeletes;

    protected $fillable = [
        'user_id',
        'patrol_id',
        'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->user_id)) {
                $model->user_id = auth('sanctum')->id();
            }
        });
    }

    public function patrol()
    {
        return $this->belongsTo(Patrol::class);
    }

    public function userPatrolTasks()
    {
        return $this->hasMany(UserPatrolTask::class);
    }

    public function userPatrolMovements()
    {
        return $this->hasMany(UserPatrolMovement::class);
    }
}
