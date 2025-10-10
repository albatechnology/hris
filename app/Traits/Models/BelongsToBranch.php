<?php

namespace App\Traits\Models;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBranch
{
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeWhenBranch(Builder $query, ?int $branchId = null): Builder
    {
        return $query->when($branchId, fn($q) => $q->where('branch_id', $branchId));
    }
}
