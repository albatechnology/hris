<?php

namespace App\Models;

use DateTimeInterface;
use Spatie\Permission\Models\Role as ModelsRole;

class Role extends ModelsRole
{
    public $table = 'roles';

    protected $fillabe = [
        'name'
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d-m-Y H:i');
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
