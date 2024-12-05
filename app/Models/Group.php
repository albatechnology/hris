<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends BaseModel implements TenantedInterface
{
    protected $fillable = ['name'];

    public function scopeTenanted(Builder $query): Builder
    {
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) return $query;

        return $query->where('id', $user->group_id);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
