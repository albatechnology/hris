<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CreatedUpdatedInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolHour extends BaseModel implements TenantedInterface
{
    use CreatedUpdatedInfo;

    protected $fillable = [
        'patrol_id',
        'start_hour',
        'end_hour',
        'description',
    ];

    // public function scopeTenanted(Builder $query): Builder
    // {
    //     /** @var User $user */
    //     $user = auth('sanctum')->user();
    //     if ($user->is_super_admin) return $query;

    //     return $query->whereHas('patrol', fn($q) => $q->tenanted());
    // }

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        return $query->whereHas('patrol', fn($q) => $q->tenanted($user));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function patrol(): BelongsTo
    {
        return $this->belongsTo(Patrol::class);
    }
}
