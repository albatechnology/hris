<?php

namespace App\Models;

use App\Enums\UserType;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeoffQuotaHistory extends BaseModel
{
    use BelongsToUser, SoftDeletes, CreatedUpdatedInfo;

    protected $fillable = [
        'user_id',
        'timeoff_quota_id',
        'is_increment',
        'old_balance',
        'new_balance',
        'description',
    ];

    protected $casts = [
        'is_increment' => 'boolean',
        'old_balance' => 'float',
        'new_balance' => 'float',
    ];

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) {
            return $query;
        }

        if ($user->is_administrator) {
            return $query->whereHas('user', fn($q) => $q->whereIn('type', [UserType::ADMINISTRATOR, UserType::USER])->where('group_id', $user->group_id));
        }

        if ($user->is_admin) {
            $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

            return $query->whereHas('user', fn($q) => $q->whereTypeUnder($user->type)->whereHas('companies', fn($q) => $q->where('company_id', $companyIds)));
        }

        $userIds = \Illuminate\Support\Facades\DB::table('user_supervisors')->select('user_id')->where('supervisor_id', $user->id)->get()?->pluck('user_id')->all() ?? [];

        if (count($userIds) > 0) {
            return $query->whereIn('user_id', [...$userIds, $user->id]);
        }

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

    public function timeoffQuota(): BelongsTo
    {
        return $this->belongsTo(TimeoffQuota::class);
    }
}
