<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RequestChangeData extends BaseModel implements HasMedia
{
    use BelongsToUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'description',
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approval_status' => ApprovalStatus::class
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->approved_by = $model->user->approval?->id ?? null;
        });
    }

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

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        return $query->whereHas('user', fn ($q) => $q->whereIn('company_id', $companyIds));
    }

    public function details(): HasMany
    {
        return $this->hasMany(RequestChangeDataDetail::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
