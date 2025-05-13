<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'address',
        'pic_name',
        'pic_email',
        'pic_phone',
    ];

    public function clientLocations(): HasMany
    {
        return $this->hasMany(ClientLocation::class);
    }

    public function patrols(): HasMany
    {
        return $this->hasMany(Patrol::class);
    }

    public function guestBooks(): HasMany
    {
        return $this->hasMany(GuestBook::class);
    }

    public function scopeSelectMinimalist(Builder $query, array $additionalColumns = [])
    {
        $query->select(['clients.id', 'clients.company_id', 'clients.name', 'clients.phone', 'clients.address', 'clients.pic_name', 'clients.pic_email', 'clients.pic_phone', 'clients.created_at', ...$additionalColumns]);
    }
}
