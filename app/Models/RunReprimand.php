<?php

namespace App\Models;

use App\Enums\RunPayrollStatus;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;
use Illuminate\Database\Eloquent\Model;

class RunReprimand extends Model implements TenantedInterface
{
    use CreatedUpdatedInfo, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'status' => RunPayrollStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->status)) {
                $model->status = RunPayrollStatus::REVIEW;
            }
        });
    }
}
