<?php

namespace App\Models;

use App\Enums\RunPayrollStatus;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunThr extends BaseModel
{
    use BelongsToUser, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'user_id',
        'code',
        'thr_date',
        'payment_date',
        'status',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'user_id' => 'integer',
        'code' => 'string',
        'thr_date' => 'date',
        'payment_date' => 'date',
        'status' => RunPayrollStatus::class,
    ];

    public function users(): HasMany
    {
        return $this->hasMany(RunThrUser::class);
    }

    public static function generateCode()
    {
        $companyId = auth()->user()->company_id ?? 0;
        $date = date('dmy');

        if ($latestCode = RunThr::where('code', 'LIKE', "THR.$companyId.$date.%")->latest()->first()?->code) {
            $increment = sprintf('%03s', (int)explode('.', $latestCode)[3] + 1);
        } else {
            $increment = sprintf('%03s', 1);
        }

        $code = "THR.$companyId.$date.$increment";

        return $code;
    }
}
