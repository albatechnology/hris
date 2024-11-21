<?php

namespace App\Models;

use App\Enums\UserType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeoffQuota extends BaseModel implements TenantedInterface
{
    use BelongsToUser, CustomSoftDeletes, CreatedUpdatedInfo;

    protected $fillable = [
        'timeoff_policy_id',
        'user_id',
        'effective_start_date',
        'effective_end_date',
        'quota',
        'used_quota'
    ];

    protected $casts = [
        'quota' => 'float',
        'used_quota' => 'float'
    ];

    protected $appends = ['balance'];

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

    public function getBalanceAttribute(): float
    {
        return $this->quota - $this->used_quota;
    }

    public function timeoffPolicy(): BelongsTo
    {
        return $this->belongsTo(TimeoffPolicy::class);
    }

    public function timeoffQuotaHistories(): HasMany
    {
        return $this->hasMany(TimeoffQuotaHistory::class);
    }

    public function scopeWhereExpired(Builder $query, $date = null)
    {
        if (!$date) {
            $date = date('Y-m-d');
        } else {
            $date = date('Y-m-d', strtotime($date));
        }

        $query->where(fn($q) => $q->whereNotNull('effective_end_date')->whereDate('effective_end_date', '<=', $date));
    }

    public function scopeEffectiveStartDate(Builder $query, $date = null)
    {
        if (is_null($date)) {
            return $query;
        }
        $query->whereDate('effective_start_date', '>=', date('Y-m-d', strtotime($date)));
    }


    public function scopeEffectiveEndDate(Builder $query, $date = null)
    {
        if (is_null($date)) {
            return $query;
        }
        $query->whereDate('effective_end_date', '<=', date('Y-m-d', strtotime($date)));
    }

    public function scopeWhereActive($q, ?string $startDate = null, ?string $endDate = null)
    {
        if ($startDate) {
            $startDate = date('Y-m-d', strtotime($startDate));
        } else {
            $startDate = date('Y-m-d');
        }

        if ($endDate) {
            $endDate = date('Y-m-d', strtotime($endDate));
        } else {
            $endDate = $startDate;
        }

        $q->where(
            fn($q) => $q
                ->where(
                    fn($q) => $q->whereDate('effective_start_date', '<=', $startDate)->whereDate('effective_end_date', '>=', $endDate)
                )
                ->orWhere(
                    fn($q) => $q->whereDate('effective_start_date', '<=', $startDate)->whereNull('effective_end_date')
                )
        )
            ->whereRaw('quota > used_quota');
    }
}
