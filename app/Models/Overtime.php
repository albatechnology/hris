<?php

namespace App\Models;

use App\Enums\RateType;
use App\Traits\Models\BelongsToBranch;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\MorphManyFormulas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Overtime extends BaseModel
{
    use CompanyTenanted, MorphManyFormulas, BelongsToBranch;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'is_rounding',
        'compensation_rate_per_day',
        'rate_type',
        'rate_amount',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'name' => 'string',
        'is_rounding' => 'boolean',
        'compensation_rate_per_day' => 'integer',
        'rate_type' => RateType::class,
        'rate_amount' => 'double',
    ];

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        if (config('app.name') === 'SYNTEGRA') {
            return $query->whereHas('branch', fn($q) => $q->tenanted($user));
        }

        return $query->whereHas('company', fn($q) => $q->tenanted($user));
    }

    public function overtimeAllowances(): HasMany
    {
        return $this->hasMany(OvertimeAllowance::class);
    }

    public function overtimeMultipliers(): HasMany
    {
        return $this->hasMany(OvertimeMultiplier::class);
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function overtimeRoundings(): HasMany
    {
        return $this->hasMany(OvertimeRounding::class);
    }
}
