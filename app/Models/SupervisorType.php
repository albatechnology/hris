<?php

namespace App\Models;

use App\Traits\Models\CompanyTenanted;

class SupervisorType extends BaseModel implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'name',
        'order',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'name' => 'string',
        'order' => 'integer',
    ];
}
