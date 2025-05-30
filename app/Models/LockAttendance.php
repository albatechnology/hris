<?php

namespace App\Models;

use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;
use Illuminate\Database\Eloquent\Builder;

class LockAttendance extends BaseModel
{
    use CompanyTenanted, CreatedUpdatedInfo;

    protected $fillable = [
        'company_id',
        'start_date',
        'end_date',
    ];

    public function scopeWhereDate(Builder $query, string $date)
    {
        $query->whereDate('start_date', '<=', date('Y-m-d', strtotime($date)))->whereDate('end_date', '>=', date('Y-m-d', strtotime($date)));
    }
}
