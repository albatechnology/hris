<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class UserPatrolTask extends BaseModel implements HasMedia
{
    use BelongsToUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'patrol_task_id',
        'description',
    ];

    public function patrolTask(): BelongsTo
    {
        return $this->belongsTo(PatrolTask::class);
    }
}
