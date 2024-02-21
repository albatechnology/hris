<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends BaseModel implements TenantedInterface
{
    protected $fillable = [
        'division_id',
        'name',
    ];

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) {
            return $query;
        }

        return $query->whereHas('division', fn ($q) => $q->whereHas('company', fn ($q) => $q->where('group_id', $user->group_id)));

        // $branchIds = $user->branches()->get(['id'])?->pluck('id') ?? [];
        // return $query->whereIn('id', $branchIds);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
