<?php

namespace App\Models;

use App\Enums\ScheduleType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shift extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'type',
        'is_dayoff',
        'name',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
        'color',
        'description',
        'is_enable_validation',
        'clock_in_min_before',
        'clock_out_max_after',
        'is_enable_grace_period',
        'clock_in_dispensation',
        'clock_out_dispensation',
        'is_enable_auto_overtime',
        'overtime_before',
        'overtime_after',
    ];

    protected $casts = [
        'type' => ScheduleType::class,
        'is_dayoff' => 'boolean',
        'is_enable_validation' => 'boolean',
        'is_enable_grace_period' => 'boolean',
        'is_enable_auto_overtime' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (! $model->is_enable_validation) {
                $model->clock_in_min_before = 0;
                $model->clock_out_max_after = 0;
            }

            if (! $model->is_enable_grace_period) {
                $model->clock_in_dispensation = 0;
                $model->clock_out_dispensation = 0;
            }

            if (! $model->is_enable_auto_overtime) {
                $model->overtime_before = null;
                $model->overtime_after = null;
            }
        });
    }

    public function scopeSelectMinimalist($q, array $additionalColumns = [])
    {
        $q->select(['id', 'is_dayoff', 'name', 'clock_in', 'clock_out', ...$additionalColumns]);
    }

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'schedule_shifts');
    }
}
