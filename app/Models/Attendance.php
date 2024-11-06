<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceType;
use App\Enums\UserType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Attendance extends BaseModel implements TenantedInterface, HasMedia
{
    use CustomSoftDeletes, BelongsToUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'schedule_id',
        'shift_id',
        'timeoff_id',
        'event_id',
        'code',
        'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->user_id)) {
                $model->user_id = auth('sanctum')->id();
            }
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
            return $query->whereHas('user', fn ($q) => $q->whereIn('type', [UserType::ADMINISTRATOR, UserType::USER])->where('group_id', $user->group_id));
        }

        // if ($user->descendants()->exists()) {
        //     return $query->whereHas('user', fn ($q) => $q->whereDescendantOf($user));
        // }

        return $query->where('user_id', $user->id);
        // $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        // return $query->whereHas('user', fn ($q) => $q->whereIn('company_id', $companyIds));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

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

    public function scopeValid(Builder $query)
    {
        $query->whereHas('details', fn($q) => $q->whereIn('type', [AttendanceType::AUTOMATIC, AttendanceType::OTHER])->orWhere(fn($q) => $q->where('type', AttendanceType::MANUAL)->where('approval_status', ApprovalStatus::APPROVED)));
    }

    public function timeoff(): BelongsTo
    {
        return $this->belongsTo(Timeoff::class);
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

    public function clockIn(): HasOne
    {
        return $this->hasOne(AttendanceDetail::class)->where('is_clock_in', true)->orderByDesc('attendance_details.id');
    }

    public function clockOut(): HasOne
    {
        return $this->hasOne(AttendanceDetail::class)->where('is_clock_in', false)->orderByDesc('attendance_details.id');
    }

    public function scopeWhereDateBetween(Builder $query, string $startDate, string $endDate)
    {
        $query->whereDate('date', '>=', date('Y-m-d', strtotime($startDate)))->whereDate('date', '<=', date('Y-m-d', strtotime($endDate)));
    }
}
