<?php

namespace App\Models;

use App\Enums\WorkingPeriod;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'name',
        'min_working_hour',
        'working_period',
        'description',
        'weekday_overtime_rate',
        'weekend_overtime_rate',
    ];

    protected $casts = [
        'working_period' => WorkingPeriod::class,
    ];

    public function hours(): HasMany
    {
        return $this->hasMany(TaskHour::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_tasks');
    }
}
