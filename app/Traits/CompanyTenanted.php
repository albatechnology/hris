<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

trait CompanyTenanted
{
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) return $query;
        if ($user->is_administrator) return $query->whereHas('company', fn ($q) => $q->where('group_id', $user->group_id));

        $companyIds =  $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
        return $query->whereIn('company_id', $companyIds);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) return $query->firstOrFail();
        return $query->first();
    }
}
