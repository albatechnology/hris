<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'country',
        'province',
        'city',
        'zip_code',
        'lat',
        'lng',
        'address',
        'bank_name',
        'bank_account_no',
        'bank_account_holder',
        'bank_code',
        'bank_branch',
    ];

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) {
            return $query;
        }

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
        $query->whereIn('company_id', $companyIds);

        if ($user->is_admin) {
            return $query;
            // return $query->whereHas('company', fn($q) => $q->where('group_id', $user->group_id));
        }

        $branchIds = $user->branches()->get(['branch_id'])?->pluck('branch_id') ?? [];

        return $query->whereIn('id', $branchIds);
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
