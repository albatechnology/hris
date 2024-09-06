<?php

namespace App\Models;

use App\Traits\Models\CompanyTenanted;

class Npp extends BaseModel
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'name',
        'number',
        'jkk',
    ];
}
