<?php

namespace App\Models;

use App\Enums\UserType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends BaseModel implements TenantedInterface
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'schedule_id',
        'shift_id',
        'timeoff_id',
        'event_id',
        'code',
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

    public function scopeWhereCompanyId(Builder $query, int $companyId)
    {
        $query->whereHas('user', fn ($q) => $q->where('company_id', $companyId));
    }

    public function scopeWhereShiftId(Builder $query, int $shiftId)
    {
        $query->whereHas('schedule', fn ($q) => $q->whereHas('shifts', fn ($q) => $q->where('shift_id', $shiftId)));
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AttendanceDetail::class);
    }
}
