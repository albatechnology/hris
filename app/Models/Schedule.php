<?php

namespace App\Models;

use App\Enums\ScheduleType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schedule extends BaseModel implements TenantedInterface
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
        'deleted_by',
    ];

    protected $casts = [
        'type' => ScheduleType::class,
        'is_overide_national_holiday' => 'boolean',
        'is_overide_company_holiday' => 'boolean',
        'is_include_late_in' => 'boolean',
        'is_include_early_out' => 'boolean',
        'is_flexible' => 'boolean',
        'is_generate_timeoff' => 'boolean',
    ];

    public function shifts(): BelongsToMany
    {
        // return $this->belongsToMany(Shift::class, 'schedule_shifts')->using(ScheduleShift::class)->withPivot('order');
        return $this->belongsToMany(Shift::class, 'schedule_shifts')->withPivot('order');
    }

    public function shift()
    {
        return $this->hasOneThrough(Shift::class, ScheduleShift::class, 'schedule_id', 'id', 'id', 'shift_id');
        // return $this->hasOne(ScheduleShift::class)->orderByDesc('order');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_schedules', 'schedule_id', 'user_id');
    }
}
