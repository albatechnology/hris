<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveAttendance extends BaseModel implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'name',
        'is_flexible',
    ];

    protected $casts = [
        'is_flexible' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleted(function (self $model) {
            $model->users()->update(['live_attendance_id' => null]);
        });
    }

    public function locations(): HasMany
    {
        return $this->hasMany(LiveAttendanceLocation::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
