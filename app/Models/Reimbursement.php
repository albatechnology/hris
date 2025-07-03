<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Reimbursement extends RequestedBaseModel implements HasMedia, TenantedInterface
{
    use CreatedUpdatedInfo, CustomSoftDeletes, InteractsWithMedia, TenantedThroughUser;

    protected $fillable = [
        'reimbursement_category_id',
        'user_id',
        'date',
        'amount',
        'description',
    ];

    protected $appends = [
        'approval_status',
        'files',
    ];

    public function getFilesAttribute()
    {
        $files = $this->getMedia(\App\Enums\MediaCollection::REIMBURSEMENT->value);
        $data = [];
        foreach ($files as $file) {
            $data[] = $file;
        }

        return $data;
    }

    public function reimbursementCategory(): BelongsTo
    {
        return $this->belongsTo(ReimbursementCategory::class);
    }

    public function scopeWhereDateBetween($query, $start, $end)
    {
        return $query->whereBetween('date', [date('Y-m-d', strtotime($start)), date('Y-m-d', strtotime($end))]);
    }

    public function scopeWhereYearIs(Builder $query, ?string $year = null)
    {
        if (is_null($year)) {
            $year = date('Y');
        }

        $query->whereYear('date', $year);
    }

    public function scopeWhereMonthIs(Builder $query, ?string $month = null)
    {
        if (is_null($month)) {
            $month = date('m');
        }

        $query->whereMonth('date', $month);
    }
}
