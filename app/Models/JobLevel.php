<?php

namespace App\Models;

use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobLevel extends BaseModel
{
    use CustomSoftDeletes, CreatedUpdatedInfo, CompanyTenanted;

     protected $fillable = [
        'company_id',
        'name',
        'code',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
