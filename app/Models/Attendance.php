<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Attendance extends BaseModel implements TenantedInterface, HasMedia
{
    use CustomSoftDeletes, CreatedUpdatedInfo, TenantedThroughUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'schedule_id',
        'shift_id',
        'timeoff_id',
        'event_id',
        'reprimand_id',
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

    public function scopeWhereCompanyId(Builder $query, int $companyId)
    {
        $query->whereHas('user', fn($q) => $q->where('company_id', $companyId));
    }

    public function scopeWhereShiftId(Builder $query, int $shiftId)
    {
        $query->whereHas('schedule', fn($q) => $q->whereHas('shifts', fn($q) => $q->where('shift_id', $shiftId)));
    }

    public function scopeValid(Builder $query)
    {
        $query->whereHas('details', fn($q) => $q->approved());
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

    public function reprimand(): BelongsTo
    {
        return $this->belongsTo(Reprimand::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AttendanceDetail::class);
    }

    public function clockIn(): HasOne
    {
        return $this->hasOne(AttendanceDetail::class)->where('is_clock_in', true)->orderBy('attendance_details.time');
    }

    public function clockOut(): HasOne
    {
        return $this->hasOne(AttendanceDetail::class)->where('is_clock_in', false)->orderByDesc('attendance_details.time');
    }

    /**
     * Scope a query to only include records where the date is between the specified start and end dates.
     *
     * @param Builder $query The query builder instance.
     * @param string $startDate The start date for filtering records.
     * @param string $endDate The end date for filtering records.
     * @return void
     */
    public function scopeWhereDateBetween(Builder $query, string $startDate, string $endDate)
    {
        $query->whereDate('date', '>=', date('Y-m-d', strtotime($startDate)))->whereDate('date', '<=', date('Y-m-d', strtotime($endDate)));
    }
}
