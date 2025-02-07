<?php

namespace App\Models;

use App\Enums\BankName;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;

class Bank extends BaseModel
{
    use CustomSoftDeletes, CreatedUpdatedInfo, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'name',
        'account_no',
        'account_holder',
        'code',
        'branch',
    ];

    protected $casts = [
        'name' => BankName::class,
    ];
}
