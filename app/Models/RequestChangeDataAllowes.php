<?php

namespace App\Models;

use App\Enums\RequestChangeDataType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Model;

class RequestChangeDataAllowes extends Model implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'type',
    ];

    protected $casts = [
        'type' => RequestChangeDataType::class,
    ];
}
