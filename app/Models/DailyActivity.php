<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DailyActivity extends BaseModel implements TenantedInterface, HasMedia
{
    use TenantedThroughUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'title',
        'start_at',
        'end_at',
        'description',
    ];

    public function scopeWhereCompanyId(Builder $query, $companyId)
    {
        $query->whereHas('user', fn($q) => $q->where('company_id', $companyId));
    }

    public function scopeWhereBranchId(Builder $query, $branchId)
    {
        $query->whereHas('user', fn($q) => $q->where('branch_id', $branchId));
    }

    public function scopeStartAt(Builder $query, $date)
    {
        $query->whereDate('start_at', '>=', date('Y-m-d', strtotime($date)));
    }

    public function scopeEndAt(Builder $query, $date)
    {
        $query->whereDate('end_at', '<=', date('Y-m-d', strtotime($date)));
    }
}
