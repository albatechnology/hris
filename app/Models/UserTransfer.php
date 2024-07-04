<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\EmploymentStatus;
use App\Enums\TransferType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTransfer extends BaseModel implements TenantedInterface
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'from',
        'type',
        'effective_date',
        'employment_status',
        'branch_id',
        'approval_id',
        'parent_id',
        'reason',
        'is_notify_manager',
        'is_notify_user',
        'approval_status',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'from' => 'array',
        'type' => TransferType::class,
        'employment_status' => EmploymentStatus::class,
        'is_notify_manager' => 'boolean',
        'is_notify_user' => 'boolean',
        'approval_status' => ApprovalStatus::class,
    ];

    protected static function booted(): void
    {
        static::updating(function (self $model) {
            if ($model->isDirty('approval_status') && $model->approval_status->is(ApprovalStatus::APPROVED)) {
                $model->from = [
                    'employment_status' => $model->user->detail->employment_status,
                    'branch_id' => $model->user->branch->name,
                    'approval_id' => $model->user->approval?->name ?? null,
                    'parent_id' => $model->user->manager?->name ?? null,
                ];
            }
        });
    }

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
            $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
            return $query->whereHas('user', fn ($q) => $q->whereIn('company_id', $companyIds));
        }

        if ($user->descendants()->exists()) {
            return $query->whereHas('user', fn ($q) => $q->whereDescendantOf($user));
        }

        return $query->where(fn ($q) => $q->whereHas('user', fn ($q) => $q->where('approval_id', $user->id))->orWhere('user_id', $user->id)->where('approval_id', $user->id));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_id');
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
