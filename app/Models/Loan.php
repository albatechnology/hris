<?php

namespace App\Models;

use App\Enums\LoanType;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends BaseModel
{
    use CustomSoftDeletes, CreatedUpdatedInfo, TenantedThroughUser;

    protected $fillable = [
        'user_id',
        'code',
        'effective_date',
        'type',
        'installment',
        'interest',
        'amount',
        'description',
    ];

    protected $casts = [
        'type' => LoanType::class,
    ];

    public function details(): HasMany {
        return $this->hasMany(LoanDetail::class);
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
