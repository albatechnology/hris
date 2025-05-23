<?php

namespace App\Models;

use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;

class LockAttendance extends BaseModel
{
    use CompanyTenanted, CreatedUpdatedInfo;

    protected $fillable = [
        'company_id',
        'start_date',
        'end_date',
    ];
}
