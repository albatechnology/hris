<?php

namespace App\Models;

use App\Enums\UpdatePayrollComponentType;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UpdatePayrollComponent extends BaseModel
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'transaction_id',
        'type',
        'description',
        'effective_date',
        'end_date',
        'backpay_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'transaction_id' => 'integer',
        'type' => UpdatePayrollComponentType::class,
        'description' => 'string',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $latestUpdatePayrollComponent = UpdatePayrollComponent::whereDate('created_at', now())->latest()->first();
            if ($latestUpdatePayrollComponent) {
                $transactionIdIncrement = substr($latestUpdatePayrollComponent->transaction_id, -3);
                $transactionIdIncrement = (int)$transactionIdIncrement + 1;
            } else {
                $transactionIdIncrement = 1;
            }

            $model->transaction_id = date('Y') . date('m') . date('d') . sprintf('%03d', $transactionIdIncrement);
            $model->created_by = auth('sanctum')->id();
        });

        static::updating(function (self $model) {
            $model->updated_by = auth('sanctum')->id();
        });
    }

    public function details(): HasMany
    {
        return $this->hasMany(UpdatePayrollComponentDetail::class);
    }

    public function firstDetail(): HasOne
    {
        return $this->hasOne(UpdatePayrollComponentDetail::class);
    }
}
