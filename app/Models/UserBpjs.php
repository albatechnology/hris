<?php

namespace App\Models;

use App\Enums\PaidBy;

class UserBpjs extends BaseModel
{
    protected $fillable = [
        'user_id',
        'upah_bpjs_kesehatan',
        'upah_bpjs_ketenagakerjaan',
        'bpjs_ketenagakerjaan_no',
        'npp_id',
        'bpjs_ketenagakerjaan_date',
        'bpjs_kesehatan_no',
        'bpjs_kesehatan_family_no',
        'bpjs_kesehatan_date',
        'bpjs_kesehatan_cost',
        'jht_cost',
        'jaminan_pensiun_cost',
        'jaminan_pensiun_date',
    ];

    protected $casts = [
        'bpjs_kesehatan_cost' => PaidBy::class,
        'jht_cost' => PaidBy::class,
        'jaminan_pensiun_cost' => PaidBy::class,
    ];
}
