<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes;

    protected $fillable = [
        'division_id',
        'name',
    ];

    public function scopeTenanted(Builder $query): Builder
    {
        return $query->whereHas('division', fn($q) => $q->tenanted());
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function scopeCompanyId(Builder $query, int $value)
    {
        $query->whereHas('division', fn($q) => $q->where('divisions.company_id', $value));
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
