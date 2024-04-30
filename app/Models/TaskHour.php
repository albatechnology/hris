<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaskHour extends BaseModel
{
    protected $fillable = [
        'task_id',
        'name',
        'min_working_hour',
        'max_working_hour',
        'hours',
    ];

    protected $casts = [
        'hours' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_tasks', 'task_hour_id');
    }
}
