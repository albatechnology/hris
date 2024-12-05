<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class LiveAttendanceLocation extends BaseModel implements TenantedInterface
{
    protected $fillable = [
        'live_attendance_id',
        'name',
        'radius',
        'lat',
        'lng',
    ];

    public function scopeTenanted(Builder $query): Builder
    {
        return $query->whereHas('liveAttendance', fn($q) => $q->tenanted());
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function liveAttendance(): BelongsTo
    {
        return $this->belongsTo(LiveAttendance::class);
    }
}
