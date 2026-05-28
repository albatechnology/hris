<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'department_id',
        'user_id',
        'name',
        'order',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
