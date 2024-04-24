<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
