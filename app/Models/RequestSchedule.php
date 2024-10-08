<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\ScheduleType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestSchedule extends BaseModel implements TenantedInterface
{
    use CompanyTenanted, CustomSoftDeletes;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'effective_date',
        'is_overide_national_holiday',
        'is_overide_company_holiday',
        'is_include_late_in',
        'is_include_early_out',
        'is_flexible',
        'is_generate_timeoff',
        'description',
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'type' => ScheduleType::class,
        'is_overide_national_holiday' => 'boolean',
        'is_overide_company_holiday' => 'boolean',
        'is_include_late_in' => 'boolean',
        'is_include_early_out' => 'boolean',
        'is_flexible' => 'boolean',
        'is_generate_timeoff' => 'boolean',
        'approval_status' => ApprovalStatus::class
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->approved_by = $model->user->approval?->id ?? null;
        });
    }

    public function requestScheduleShifts(): HasMany
    {
        return $this->hasMany(RequestScheduleShift::class);
    }

    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, RequestScheduleShift::class)->withPivot('order');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
