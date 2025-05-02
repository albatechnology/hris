<?php

namespace App\Models;

use App\Enums\NppBpjsKetenagakerjaan;
use App\Enums\PaidBy;
use App\Traits\Models\BelongsToUser;

class UserBpjs extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'upah_bpjs_kesehatan',
        'upah_bpjs_ketenagakerjaan',
        'bpjs_ketenagakerjaan_no',
        // 'npp_id',
        'bpjs_ketenagakerjaan_date',
        'bpjs_kesehatan_no',
        'bpjs_kesehatan_family_no',
        'bpjs_kesehatan_date',
        'bpjs_kesehatan_cost',
        'jht_cost',
        'jaminan_pensiun_cost',
        'jaminan_pensiun_date',
        'npp_bpjs_ketenagakerjaan',
    ];

    protected $casts = [
        'bpjs_kesehatan_cost' => PaidBy::class,
        'jht_cost' => PaidBy::class,
        'jaminan_pensiun_cost' => PaidBy::class,
        'npp_bpjs_ketenagakerjaan' => NppBpjsKetenagakerjaan::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->upah_bpjs_kesehatan)) {
                // $upah_bpjs_kesehatan = $model->user->payrollInfo->basic_salary > 12000000 ? 12000000 : $model->user->payrollInfo->basic_salary;
                $model->upah_bpjs_kesehatan = $model->user->payrollInfo->basic_salary;
            }

            if (empty($model->upah_bpjs_ketenagakerjaan)) {
                // $upah_bpjs_ketenagakerjaan = $model->user->payrollInfo->basic_salary > 10547400 ? 10547400 : $model->user->payrollInfo->basic_salary;
                $model->upah_bpjs_ketenagakerjaan = $model->user->payrollInfo->basic_salary;
            }

            if (empty($model->bpjs_kesehatan_cost)) {
                $model->bpjs_kesehatan_cost = PaidBy::COMPANY;
            }

            if (empty($model->jht_cost)) {
                $model->jht_cost = PaidBy::COMPANY;
            }

            if (empty($model->jaminan_pensiun_cost)) {
                $model->jaminan_pensiun_cost = PaidBy::COMPANY;
            }

            if (empty($model->npp_bpjs_ketenagakerjaan)) {
                $model->npp_bpjs_ketenagakerjaan = NppBpjsKetenagakerjaan::DEFAULT;
            }
        });
    }
}
