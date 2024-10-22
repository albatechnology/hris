<?php

namespace App\Models;

use App\Enums\JkkTier;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\SoftDeletes;

class Npp extends BaseModel
{
    use CompanyTenanted, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'number',
        'jkk_tier',
    ];

    protected $casts = [
        'jkk_tier' => JkkTier::class,
    ];
}
