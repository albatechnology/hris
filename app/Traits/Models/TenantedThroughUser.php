<?php

namespace App\Traits\Models;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Builder;

trait TenantedThroughUser
{
    use BelongsToUser;

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        if ($user->is_super_admin) return $query;

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
        $query->whereHas('user', fn($q) => $q->whereIn('company_id', $companyIds));

        if ($user->is_admin) {
            return $query;
            // return $query->whereHas('user', fn($q) => $q->where('group_id', $user->group_id));
        }

        if (UserService::hasDescendants($user)) {
            return $query->where(function ($q) use ($user) {
                $q->whereHas('user', fn($q) => $q->whereHas('supervisors', fn($q) => $q->where('supervisor_id', $user->id)))->orWhere('user_id', $user->id);
            });
        }

        // if ($user->is_admin) {
        //     $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        //     return $query->whereHas('user', fn($q) => $q->whereTypeUnder($user->type)->whereHas('companies', fn($q) => $q->where('company_id', $companyIds)));
        // }

        return $query->where('user_id', $user->id);
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
