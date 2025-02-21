<?php

namespace App\Models;

use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;

class ExtraOff extends BaseModel
{
    use CreatedUpdatedInfo, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'user_ids',
    ];

    protected $casts = [
        'user_ids' => 'array',
    ];
}
