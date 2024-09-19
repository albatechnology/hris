<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patrol extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'start_date',
        'end_date',
        'lat',
        'lng',
        'description',
    ];

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
            return $query->whereHas('client', fn($q) => $q->whereHas('company', fn($q) => $q->where('group_id', $user->group_id)));
        }

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        return $query->whereHas('client', fn($q) => $q->whereIn('company_id', $companyIds));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function patrolLocations(): HasMany
    {
        return $this->hasMany(PatrolLocation::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserPatrol::class);
    }
}
