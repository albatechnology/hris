<?php

namespace App\Models;

use App\Enums\TimeoffRequestType;
use App\Enums\UserType;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timeoff extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'timeoff_policy_id',
        'request_type',
        'start_at',
        'end_at',
        'delegate_to',
        'reason',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'request_type' => TimeoffRequestType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->user_id)) $model->user_id = auth('sanctum')->user()->id;
        });
    }

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) return $query;
        if ($user->is_administrator) {
            return $query->whereHas('user', fn ($q) => $q->whereIn('type', [UserType::ADMINISTRATOR, UserType::USER])->where('group_id', $user->group_id));
        }

        $companyIds =  $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
        return $query->whereHas('user', fn ($q) => $q->whereIn('company_id', $companyIds));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) return $query->firstOrFail();
        return $query->first();
    }

    public function scopeStartAt(Builder $query, $date = null)
    {
        if (is_null($date)) return $query;
        $query->whereDate('start_at', '>=', date('Y-m-d', strtotime($date)));
    }

    public function scopeEndAt(Builder $query, $date = null)
    {
        if (is_null($date)) return $query;
        $query->whereDate('end_at', '<=', date('Y-m-d', strtotime($date)));
    }

    public function timeoffPolicy(): BelongsTo
    {
        return $this->belongsTo(TimeoffPolicy::class);
    }

    public function delegateTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_to');
    }
}
