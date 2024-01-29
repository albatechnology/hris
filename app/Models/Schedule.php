<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        return $this->belongsToMany(Shift::class);
    }
}
