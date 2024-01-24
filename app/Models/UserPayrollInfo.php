<?php

namespace App\Models;

use App\Enums\PtkpStatus;
use App\Traits\BelongsToUser;

class UserPayrollInfo extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'bpjs_ketenagakerjaan_no',
        'bpjs_kesehatan_no',
        'npwp',
        'bank_name',
        'bank_account_no',
        'bank_account_holder',
        'ptkp_status',
    ];

    protected $casts = [
        'ptkp_status' => PtkpStatus::class,
    ];
}
