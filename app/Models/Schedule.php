<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\ScheduleType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schedule extends BaseModel implements TenantedInterface
{
    use CompanyTenanted, CustomSoftDeletes, CreatedUpdatedInfo;

    // columns description, approval_status(pending/approved/rejected), approved_by, approved_at used to supervisor request schedule for their descendant
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
        'description',
        'is_need_approval',
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
        'is_need_approval' => 'boolean',
        'approval_status' => ApprovalStatus::class,
    ];

    public function scopeRequestTenanted(Builder $query, ?User $user = null): Builder
    {
        $query->where('is_need_approval', 1);
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        if ($user->is_super_admin) return $query;

        $query->tenanted();

        if ($user->is_admin) return $query;

        return $query->where('created_by', $user->id);
    }

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
