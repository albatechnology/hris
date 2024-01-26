<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as ModelsRole;

class Role extends ModelsRole implements TenantedInterface
{
    public $table = 'roles';

    protected $fillable = [
        'group_id',
        'name',
        'guard_name',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $user = auth('sanctum')->user();
            if (empty($model->group_id) && !$user->is_super_admin) $model->group_id = $user->group_id;
        });
    }


    public function scopeTenanted(Builder $query): Builder
    {
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) return $query;

        return $query->where('group_id', $user->group_id);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) return $query->firstOrFail();
        return $query->first();
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    // public function scopeTenanted($query)
    // {
    //     $hasActiveTenant = tenancy()->getActiveTenant();
    //     if ($hasActiveTenant) return $query->whereHas('tenants', fn ($q) => $q->where('tenant_id', $hasActiveTenant->id));

    //     $hasActiveCompany = tenancy()->getActiveCompany();
    //     if ($hasActiveCompany) return $query->whereHas('companies', fn ($q) => $q->where('company_id', $hasActiveCompany->id));

    //     $user = user();
    //     return $user->is_super_admin ? $query : $query->whereHas('tenants', fn ($q) => $q->whereIn('tenant_id', tenancy()->getTenants()->pluck('id')));
    // }

    // public function scopeFindTenanted($query, int $id)
    // {
    //     return $query->tenanted()->where('id', $id)->firstOrFail();
    // }

    // public function company()
    // {
    //     return $this->belongsTo(Company::class, 'company_id');
    // }

    // public function scopeWherePublicRole($query)
    // {
    //     return $query->where('company_id', '!=', 1);
    // }
}
