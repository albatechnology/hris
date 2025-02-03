<?php

namespace App\Models;

use App\Enums\TimeoffRequestType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Timeoff extends RequestedBaseModel implements HasMedia, TenantedInterface
{
    use CustomSoftDeletes, BelongsToUser, InteractsWithMedia, TenantedThroughUser;

    protected $fillable = [
        'user_id',
        'timeoff_policy_id',
        'total_days',
        'request_type',
        'start_at',
        'end_at',
        'delegate_to',
        'reason',
        'is_advanced_leave',
        // 'approval_status',
        // 'approved_by',
        // 'approved_at',
    ];

    protected $casts = [
        'total_days' => 'float',
        'request_type' => TimeoffRequestType::class,
        // 'approval_status' => ApprovalStatus::class,
    ];

    protected $appends = [
        'approval_status',
        'files',
    ];


    // public function scopeApproved(Builder $query)
    // {
    //     $query->where('approval_status', ApprovalStatus::APPROVED);
    // }

    // public function scopeStartAt(Builder $query, $date = null)
    // {
    //     if (is_null($date)) {
    //         return $query;
    //     }
    //     $query->whereDate('start_at', '>=', date('Y-m-d', strtotime($date)));
    // }

    // public function scopeEndAt(Builder $query, $date = null)
    // {
    //     if (is_null($date)) {
    //         return $query;
    //     }
    //     $query->whereDate('end_at', '<=', date('Y-m-d', strtotime($date)));
    // }

    public function scopeWhereBetweenStartEnd(Builder $query, string $startDate, string $endDate)
    {
        $query->whereDate('start_at', '<=', date('Y-m-d', strtotime($startDate)))->whereDate('end_at', '>=', date('Y-m-d', strtotime($endDate)));
    }

    public function timeoffPolicy(): BelongsTo
    {
        return $this->belongsTo(TimeoffPolicy::class);
    }

    public function delegateTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_to');
    }

    public function getFilesAttribute()
    {
        $files = $this->getMedia(\App\Enums\MediaCollection::TIMEOFF->value);
        $data = [];
        foreach ($files as $file) {
            $data[] = $file;
        }

        return $data;
    }

    // public function approvedBy(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }

    // public function getTotalDaysAttribute(): int|null
    // {
    //     $interval = new \DateInterval('P1D');
    //     $realEnd = new \DateTime($this->end_at);
    //     $realEnd->add($interval);
    //     $period = new \DatePeriod(new \DateTime($this->start_at), $interval, $realEnd);
    //     $this->load(['user' => fn($q) => $q->select('id')]);

    //     $schedule = \App\Services\ScheduleService::getTodaySchedule($this->user, $this->start_at)?->load(['shifts' => fn($q) => $q->orderBy('order')]);
    //     if ($schedule) {
    //         $order = $schedule->shifts->where('id', $schedule->shift->id);
    //         $orderKey = array_keys($order->toArray())[0];
    //         $totalShifts = $schedule->shifts->count();

    //         $companyHolidays = Event::tenanted()->where('company_id', $this->user->company_id)->whereCompanyHoliday()->get();
    //         $nationalHolidays = NationalHoliday::orderBy('date')->get(['date']);
    //         $totalDays = 0;
    //         foreach ($period as $date) {
    //             $totalDays += 1;
    //             $date = $date->format('Y-m-d');
    //             $shift = $schedule->shifts[$orderKey];

    //             $companyHolidayData = null;
    //             if ($schedule->is_overide_company_holiday == false) {
    //                 $companyHolidayData = $companyHolidays->first(function ($companyHoliday) use ($date) {
    //                     return date('Y-m-d', strtotime($companyHoliday->start_at)) <= $date && date('Y-m-d', strtotime($companyHoliday->end_at)) >= $date;
    //                 });

    //                 if ($companyHolidayData) {
    //                     $totalDays -= 1;
    //                 }
    //             }

    //             $nationalHoliday = null;
    //             if ($schedule->is_overide_national_holiday == false && is_null($companyHolidayData)) {
    //                 $nationalHoliday = $nationalHolidays->firstWhere('date', $date);
    //                 if ($nationalHoliday) {
    //                     $totalDays -= 1;
    //                 }
    //             }

    //             if ($shift->is_dayoff && is_null($nationalHoliday)) {
    //                 $totalDays -= 1;
    //             }

    //             if (($orderKey + 1) === $totalShifts) {
    //                 $orderKey = 0;
    //             } else {
    //                 $orderKey++;
    //             }
    //         }

    //         return $totalDays;
    //     }

    //     return null;
    // }
}
