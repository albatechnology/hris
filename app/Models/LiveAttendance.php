<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveAttendance extends BaseModel implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'name',
        'is_flexible'
    ];

    protected $casts = [
        'is_flexible' => 'boolean'
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(LiveAttendanceLocation::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_live_attendances');
    }
}
