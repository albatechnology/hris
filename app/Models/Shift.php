<?php

namespace App\Models;

use App\Enums\ScheduleType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
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
        'time_dispensation',
        'is_show_in_request',
        'is_show_in_request_for_all',
        'show_in_request_branch_ids',
        'show_in_request_department_ids',
        'show_in_request_position_ids',
        // 'is_enable_auto_overtime',
        // 'overtime_before',
        // 'overtime_after',
    ];

    protected $casts = [
        'type' => ScheduleType::class,
        'is_dayoff' => 'boolean',
        'is_enable_validation' => 'boolean',
        'is_enable_grace_period' => 'boolean',
        'is_show_in_request' => 'boolean',
        'is_show_in_request_for_all' => 'boolean',
        'show_in_request_branch_ids' => 'array',
        'show_in_request_department_ids' => 'array',
        'show_in_request_position_ids' => 'array',
        // 'is_enable_auto_overtime' => 'boolean',
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
                $model->time_dispensation = 0;
            }

            if ($model->show_in_request_branch_ids && gettype($model->show_in_request_branch_ids) == 'array') {
                $model->show_in_request_branch_ids = array_map('intval', $model->show_in_request_branch_ids);
            } else {
                $model->show_in_request_branch_ids = null;
            }

            if ($model->show_in_request_department_ids && gettype($model->show_in_request_department_ids) == 'array') {
                $model->show_in_request_department_ids = array_map('intval', $model->show_in_request_department_ids);
            } else {
                $model->show_in_request_department_ids = null;
            }

            if ($model->show_in_request_position_ids && gettype($model->show_in_request_position_ids) == 'array') {
                $model->show_in_request_position_ids = array_map('intval', $model->show_in_request_position_ids);
            } else {
                $model->show_in_request_position_ids = null;
            }
            // if (! $model->is_enable_auto_overtime) {
            //     $model->overtime_before = null;
            //     $model->overtime_after = null;
            // }
        });
    }

    public function scopeSelectMinimalist(Builder $query, array $additionalColumns = [])
    {
        $query->select(['id', 'is_dayoff', 'name', 'clock_in', 'clock_out', ...$additionalColumns]);
    }

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'schedule_shifts');
    }
}
