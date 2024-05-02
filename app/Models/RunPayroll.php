<?php

namespace App\Models;

use App\Enums\RunPayrollStep;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunPayroll extends BaseModel
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'period',
        'payment_schedule',
        'stp',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'period' => 'string',
        'payment_schedule' => 'date',
        'step' => RunPayrollStep::class,
    ];

    public function users(): HasMany
    {
        return $this->hasMany(RunPayrollUser::class);
    }
}
