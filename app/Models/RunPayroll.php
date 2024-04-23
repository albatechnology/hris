<?php

namespace App\Models;

use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunPayroll extends BaseModel
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'period',
        'payment_schedule',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'period' => 'string',
        'payment_schedule' => 'date',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(RunPayrollUser::class);
    }
}
