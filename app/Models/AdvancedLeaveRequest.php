<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvancedLeaveRequest extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'data',
        'amount',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'data' => 'array',
        'amount' => 'float',
    ];

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) {
            return $query;
        }
        if ($user->is_administrator) {
            return $query->whereHas('user', fn ($q) => $q->where('group_id', $user->group_id));
        }

        return $query->where('id', $user->id);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
