<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'name',
    ];

    // public function scopeTenanted(Builder $query): Builder
    // {
    //     /** @var User $user */
    //     $user = auth('sanctum')->user();
    //     if ($user->is_super_admin) {
    //         return $query;
    //     }

    //     return $query->whereHas('company', fn ($q) => $q->where('group_id', $user->group_id));

    //     // $branchIds = $user->branches()->get(['id'])?->pluck('id') ?? [];
    //     // return $query->whereIn('id', $branchIds);
    // }

    // public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    // {
    //     $query->tenanted()->where('id', $id);
    //     if ($fail) {
    //         return $query->firstOrFail();
    //     }

    //     return $query->first();
    // }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
