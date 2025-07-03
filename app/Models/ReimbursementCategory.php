<?php

namespace App\Models;

use App\Enums\ReimbursementPeriodType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReimbursementCategory extends BaseModel implements TenantedInterface
{
    use CreatedUpdatedInfo, CustomSoftDeletes, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'name',
        'period_type',
        'limit_amount',
    ];

    protected $casts = [
        'period_type' => ReimbursementPeriodType::class,
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_reimbursement_categories');
    }
}
