<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class UserPatrolTask extends BaseModel implements HasMedia, TenantedInterface
{
    use BelongsToUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'patrol_task_id',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->user_id)) {
                $model->user_id = auth('sanctum')->id();
            }
        });
    }

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        if ($user->is_super_admin) {
            return $query;
        }
        if ($user->is_administrator) {
            return $query->whereHas('user', fn($q) => $q->where('group_id', $user->group_id));
        }

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        return $query->whereHas('user', fn($q) => $q->whereHas('companies', fn($q) => $q->whereIn('company_id', $companyIds)));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function patrolTask(): BelongsTo
    {
        return $this->belongsTo(PatrolTask::class);
    }
}
