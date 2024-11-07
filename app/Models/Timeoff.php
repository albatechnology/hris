<?php

namespace App\Models;

use App\Enums\TimeoffRequestType;
use App\Enums\UserType;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timeoff extends RequestedBaseModel
{
    use CustomSoftDeletes, BelongsToUser;

    protected $fillable = [
        'user_id',
        'timeoff_policy_id',
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
        'request_type' => TimeoffRequestType::class,
        // 'approval_status' => ApprovalStatus::class,
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model) {
            if (empty($model->user_id)) {
                $model->user_id = auth('sanctum')->id();
            }

            // $model->approved_by = $model->user->approval?->id ?? null;
        });
    }

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) {
            return $query;
        }
        if ($user->is_administrator) {
            return $query->whereHas('user', fn($q) => $q->whereIn('type', [UserType::ADMINISTRATOR, UserType::USER])->where('group_id', $user->group_id));
        }

        if ($user->is_admin) {
            $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

            return $query->whereHas('user', fn($q) => $q->whereTypeUnder($user->type)->whereHas('companies', fn($q) => $q->where('company_id', $companyIds)));
        } else {
            $userIds = \Illuminate\Support\Facades\DB::table('user_supervisors')->where('supervisor_id', $user->id)->get(['user_id'])?->pluck('user_id')->all() ?? [];

            return $query->whereIn('user_id', [...$userIds, $user->id]);
        }

        return $query->where('user_id', $user->id);
        // $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        // return $query->whereHas('user', fn ($q) => $q->whereIn('company_id', $companyIds));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    // public function scopeApproved(Builder $query)
    // {
    //     $query->where('approval_status', ApprovalStatus::APPROVED);
    // }

    public function scopeStartAt(Builder $query, $date = null)
    {
        if (is_null($date)) {
            return $query;
        }
        $query->whereDate('start_at', '>=', date('Y-m-d', strtotime($date)));
    }

    public function scopeEndAt(Builder $query, $date = null)
    {
        if (is_null($date)) {
            return $query;
        }
        $query->whereDate('end_at', '<=', date('Y-m-d', strtotime($date)));
    }

    public function timeoffPolicy(): BelongsTo
    {
        return $this->belongsTo(TimeoffPolicy::class);
    }

    public function delegateTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_to');
    }

    // public function approvedBy(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }

    public function getTotalDaysAttribute(): int|null
    {
        $interval = new \DateInterval('P1D');
        $realEnd = new \DateTime($this->end_at);
        $realEnd->add($interval);
        $period = new \DatePeriod(new \DateTime($this->start_at), $interval, $realEnd);
        $this->load(['user' => fn($q) => $q->select('id')]);

        $schedule = \App\Services\ScheduleService::getTodaySchedule($this->user, $this->start_at)?->load(['shifts' => fn($q) => $q->orderBy('order')]);
        if ($schedule) {
            $order = $schedule->shifts->where('id', $schedule->shift->id);
            $orderKey = array_keys($order->toArray())[0];
            $totalShifts = $schedule->shifts->count();

            $companyHolidays = Event::tenanted()->where('company_id', $this->user->company_id)->whereHoliday()->get();
            $nationalHolidays = NationalHoliday::orderBy('date')->get(['date']);
            $totalDays = 0;
            foreach ($period as $date) {
                $totalDays += 1;
                $date = $date->format('Y-m-d');
                $shift = $schedule->shifts[$orderKey];

                $companyHolidayData = null;
                if ($schedule->is_overide_company_holiday == false) {
                    $companyHolidayData = $companyHolidays->first(function ($companyHoliday) use ($date) {
                        return date('Y-m-d', strtotime($companyHoliday->start_at)) <= $date && date('Y-m-d', strtotime($companyHoliday->end_at)) >= $date;
                    });

                    if ($companyHolidayData) {
                        $totalDays -= 1;
                    }
                }

                $nationalHoliday = null;
                if ($schedule->is_overide_national_holiday == false && is_null($companyHolidayData)) {
                    $nationalHoliday = $nationalHolidays->firstWhere('date', $date);
                    if ($nationalHoliday) {
                        $totalDays -= 1;
                    }
                }

                if ($shift->is_dayoff && is_null($nationalHoliday)) {
                    $totalDays -= 1;
                }

                if (($orderKey + 1) === $totalShifts) {
                    $orderKey = 0;
                } else {
                    $orderKey++;
                }
            }

            return $totalDays;
        }

        return null;
    }
}
