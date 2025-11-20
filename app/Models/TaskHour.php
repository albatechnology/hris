<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaskHour extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes;

    protected $fillable = [
        'task_id',
        'name',
        'min_working_hour',
        // 'max_working_hour',
        'hours',
    ];

    protected $casts = [
        'hours' => 'array',
    ];

    public function scopeTenanted(Builder $query): Builder
    {
        return $query->whereHas('task', fn($q) => $q->tenanted());
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_tasks', 'task_hour_id');
    }
}
