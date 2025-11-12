<?php

namespace App\Models;

use App\Enums\RunReprimandStatus;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'status' => RunReprimandStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->status)) {
                $model->status = RunReprimandStatus::REVIEW;
            }
        });
    }

    public function reprimands(): HasMany
    {
        return $this->hasMany(Reprimand::class);
    }
}
