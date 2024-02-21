<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Schedule extends BaseModel implements TenantedInterface
{
    use CompanyTenanted, CustomSoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'effective_date',
        'is_overide_national_holiday',
        'is_overide_company_holiday',
        'is_include_late_in',
        'is_include_early_out',
        'deleted_by',
    ];

    protected $casts = [
        'is_overide_national_holiday' => 'boolean',
        'is_overide_company_holiday' => 'boolean',
        'is_include_late_in' => 'boolean',
        'is_include_early_out' => 'boolean',
    ];

    public function shifts(): BelongsToMany
    {
        // return $this->belongsToMany(Shift::class, 'schedule_shifts')->using(ScheduleShift::class)->withPivot('order');
        return $this->belongsToMany(Shift::class, 'schedule_shifts')->withPivot('order');
    }

    public function shift(): HasOne
    {
        return $this->hasOne(ScheduleShift::class)->orderByDesc('order');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_schedules', 'schedule_id', 'user_id');
    }
}
