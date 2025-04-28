<?php

namespace App\Models;

use App\Enums\RunPayrollStatus;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunPayroll extends BaseModel
{
    use BelongsToUser, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'user_id',
        'code',
        'period',
        'payment_schedule',
        'status',
        'cut_off_start_date',
        'cut_off_end_date',
        'payroll_start_date',
        'payroll_end_date',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'user_id' => 'integer',
        'code' => 'string',
        'period' => 'string',
        'payment_schedule' => 'date',
        'status' => RunPayrollStatus::class,
    ];

    public function users(): HasMany
    {
        return $this->hasMany(RunPayrollUser::class);
    }

    public function scopeRelease(Builder $query)
    {
        $query->where('status', RunPayrollStatus::RELEASE);
    }

    public static function generateCode()
    {
        $companyId = auth()->user()->company_id ?? 0;
        $date = date('dmy');

        if ($latestCode = RunPayroll::where('code', 'LIKE', "TRX.$companyId.$date.%")->latest()->first()?->code) {
            $increment = sprintf('%03s', (int)explode('.', $latestCode)[3] + 1);
        } else {
            $increment = sprintf('%03s', 1);
        }

        $code = "TRX.$companyId.$date.$increment";

        return $code;
    }
}
