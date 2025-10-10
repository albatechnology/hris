<?php

namespace App\Models;

use App\Enums\UpdatePayrollComponentType;
use App\Traits\Models\BelongsToBranch;
use App\Traits\Models\CompanyTenanted;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UpdatePayrollComponent extends BaseModel
{
    use CompanyTenanted, BelongsToBranch;

    protected $fillable = [
        'company_id',
        'branch_id',
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

    public function scopeWhereActive(Builder $q, $startDate = null, $endDate = null)
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::today()->startOfDay();
        $end   = $endDate ? Carbon::parse($endDate)->startOfDay() : $start->copy();

        $q->whereDate('effective_date', '<=', $end->toDateString())
            ->where(function ($q2) use ($start) {
                $q2->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $start->toDateString());
            });

        return $q;

        // if ($startDate) {
        //     $startDate = date('Y-m-d', strtotime($startDate));
        // } else {
        //     $startDate = date('Y-m-d');
        // }

        // if ($endDate) {
        //     $endDate = date('Y-m-d', strtotime($endDate));
        // } else {
        //     $endDate = $startDate;
        // }

        // $q->where(
        //     fn($q) => $q->where(fn($q2) => $q2->whereDate('end_date', '>=', $endDate)->orWhereNull('end_date'))
        //         ->where(
        //             fn($q2) => $q2->whereDate('effective_date', '<=', $startDate)
        //                 ->orWhere(
        //                     fn($q) => $q->where('effective_date', '>=', $startDate)->where('effective_date', '<=', $endDate)
        //                 )
        //         )
        // )->orWhere(fn($q) => $q->where('effective_date', '<=', $startDate)->where('end_date', '>=', $endDate));
    }
}
