<?php

namespace App\Models;

use App\Enums\LoanType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes, CreatedUpdatedInfo, BelongsToUser;

    protected $fillable = [
        'user_id',
        'code',
        'effective_date',
        'type',
        'interest',
        'amount',
        'description',
    ];

    protected $casts = [
        'type' => LoanType::class,
    ];

    // protected $appends = [
    //     'installment',
    //     'outstanding',
    // ];

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        if ($user->is_super_admin) return $query;

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
        return $query->whereHas('user', fn($q) => $q->whereIn('company_id', $companyIds));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function details(): HasMany
    {
        return $this->hasMany(LoanDetail::class);
    }

    public function scopeWhereLoan($q)
    {
        $q->where('type', LoanType::LOAN);
    }

    public function scopeWhereInsurance($q)
    {
        $q->where('type', LoanType::INSURANCE);
    }

    public function getinstallmentAttribute(): int
    {
        return $this->details()->count();
    }

    public function getOutstandingAttribute(): int
    {
        return $this->details()->whereNull('run_payroll_user_id')->count();
    }

    public function getEndDateAttribute(): string
    {
        return date('Y-m-d', strtotime($this->effective_date . ' + ' . ($this->installment - 1) . ' month'));
    }

    public function getBalanceAttribute(): float
    {
        return max($this->amount - $this->details()->whereNotNull('run_payroll_user_id')->sum('basic_payment'), 0);
    }

    public static function generateCode()
    {
        $companyId = auth()->user()->company_id ?? 0;
        $date = date('dmy');

        if ($latestCode = self::where('code', 'LIKE', "TRX.$companyId.$date.%")->withTrashed()->latest()->first()?->code) {
            $increment = sprintf('%03s', (int)explode('.', $latestCode)[3] + 1);
        } else {
            $increment = sprintf('%03s', 1);
        }

        $code = "TRX.$companyId.$date.$increment";

        return $code;
    }
}
