<?php

namespace App\Models;

use App\Enums\RequestChangeDataType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;

class RequestChangeDataAllowes extends BaseModel implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'type',
        'is_active',
    ];

    protected $casts = [
        'type' => RequestChangeDataType::class,
        'is_active' => 'boolean',
    ];

    public static function createForCompany(Company $company): void
    {
        foreach (RequestChangeDataType::getValues() as $value) {
            self::create([
                'company_id' => $company->id,
                'type' => $value,
                'is_active' => true
            ]);
        }
    }
}
