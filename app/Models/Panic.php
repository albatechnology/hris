<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Enums\PanicStatus;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\TenantedThroughClient;

class Panic extends BaseModel implements TenantedInterface
{
    use TenantedThroughClient, BelongsToUser;

    protected $fillable = [
        'client_id',
        'user_id',
        'lat',
        'lng',
        'status',
    ];

    protected $casts = [
        'status' => PanicStatus::class,
    ];

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        if ($user->is_super_admin) return $query;

        $hasPermission = $user->roles->contains(fn($role) => $role->hasPermissionTo('allow_get_emergency_notification'));

        return $query->whereHas(
            'client',
            fn($q) => $q->tenanted()
                ->when(
                    !$user->is_admin && !$hasPermission,
                    fn($q) => $q->whereHas('user', fn($q) => $q->whereHas('supervisors', fn($q) => $q->where('supervisor_id', $user->id)))->orWhere('user_id', $user->id)
                )
        );
    }
}
