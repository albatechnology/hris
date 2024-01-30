<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Schedule extends BaseModel implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'name',
        'effective_date',
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
