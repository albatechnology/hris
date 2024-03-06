<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'order',
    ];

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) {
            return $query;
        }

        return $query->whereHas('company', fn ($q) => $q->where('group_id', $user->group_id));

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
