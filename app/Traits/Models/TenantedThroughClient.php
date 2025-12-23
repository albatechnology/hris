<?php

namespace App\Traits\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait TenantedThroughClient
{
    use BelongsToClient;

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        return $query->whereHas('client', fn($q) => $q->tenanted($user));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }
}
