<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\TimeoffPolicyType;
use App\Enums\TimeoffRequestType;
use App\Http\Requests\Api\Timeoff\StoreRequest;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Shift;
use App\Models\Timeoff;
use App\Models\TimeoffPolicy;
use App\Models\TimeoffQuota;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TimeoffService
{
    public static function checkTotalBalanceQuotaAL(int|string $userId, int|string $timeoffPolicyId, float $totalRequestDay, string $startAt, string $endAt): bool
    {
        $timeoffQuota = self::getTotalBalanceQuota($userId, $timeoffPolicyId, $startAt, $endAt);
        if ($timeoffQuota >= $totalRequestDay) return true;

        $timeoffQuota = self::getTotalBalanceQuota($userId, $timeoffPolicyId, date('Y-m-d', strtotime($startAt . '+3 month')), date('Y-m-d', strtotime($endAt . '+3 month')));
        if ($timeoffQuota >= $totalRequestDay) return true;

        return false;
    }

    public static function getTotalBalanceQuota(int|string $userId, int|string $timeoffPolicyId, ?string $startAt = null, ?string $endAt = null): float
    {
        $timeoffQuota = TimeoffQuota::where('user_id', $userId)->where('timeoff_policy_id', $timeoffPolicyId)->whereActive($startAt, $endAt)->groupBy('timeoff_policy_id')->first([DB::raw('SUM(quota) as total_quota'), DB::raw('SUM(used_quota) as total_used_quota')]);

        if (!$timeoffQuota) return 0;

        return floatval($timeoffQuota->total_quota - $timeoffQuota->total_used_quota);
    }

    public static function getTotalRequestDay(User $user, string $startDate, string $endDate, TimeoffRequestType|string $timeoffRequestType): float
    {
        $value = 0.5;
        if (!($timeoffRequestType instanceof TimeoffRequestType)) {
            $timeoffRequestType = TimeoffRequestType::from($timeoffRequestType);
        }

        if ($timeoffRequestType->isHalfDay()) return $value;

        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));

        if ($startDate === $endDate) {
            return 1;
        }

        // $startDate = date(sprintf('%s-%s-%s', $year, $month, $payrollSetting->cut_off_date));
        // $endDate = date('Y-m-d', strtotime($startDate . '+1 month'));
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate);
        $endDate = Carbon::createFromFormat('Y-m-d', $endDate);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        // $companyHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereCompanyHoliday()->get();
        $nationalHolidays = Event::select(['id', 'start_at', 'end_at'])->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereNationalHoliday()->get();

        $value = 0;
        foreach ($dateRange as $date) {
            $todaySchedule = ScheduleService::getTodaySchedule($user, $date->format('Y-m-d'), ['id', 'is_overide_national_holiday', 'is_overide_company_holiday'], ['id', 'is_dayoff']);

            $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                return date('Y-m-d', strtotime($nh->start_at)) <= $date->format("Y-m-d") && date('Y-m-d', strtotime($nh->end_at)) >= $date->format("Y-m-d");
            });


            if (($todaySchedule || $todaySchedule->shift) && !$todaySchedule->shift->is_dayoff && !$nationalHoliday) {
                $value++;
            }
        }

        return $value;
    }

    public static function requestTimeoffValidation(StoreRequest $request): StoreRequest
    {
        $user = User::findOrFail($request->user_id);

        // check what is on the request date, the user has a schedule or not
        if (!ScheduleService::checkAvailableSchedule(user: $user, startDate: $request->start_at, endDate: $request->end_at)) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Schedule is not available');
        }

        $timeoffPolicy = TimeoffPolicy::findOrFail($request->timeoff_policy_id);

        // check if request is half day, and half day is not allowed
        $isHalfDay = $request->request_type === TimeoffRequestType::HALF_DAY_BEFORE_BREAK->value || $request->request_type === TimeoffRequestType::HALF_DAY_AFTER_BREAK->value;
        if ($isHalfDay && !$timeoffPolicy->is_allow_halfday) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Half day is not allowed');
        }

        $startAtDate = date('Y-m-d', strtotime($request->start_at));
        $endAtDate = date('Y-m-d', strtotime($request->end_at));
        if ($isHalfDay && $startAtDate != $endAtDate) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Half day is not allowed for multiple days');
        }

        // if request start_at and end_at is the same day, basicly total request day is 1. but before, we need to check if the schedule is available and is dayoff or not
        // else, we need to calculate the total request day based on the start_at and end_at
        if ($startAtDate === $endAtDate) {
            $todaySchedule = ScheduleService::getTodaySchedule($user, $request->start_at);
            if (!$todaySchedule || !$todaySchedule?->shift) {
                throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Schedule is not available');
            }

            if ($todaySchedule->shift->is_dayoff === true) {
                throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Cannot request timeoff in dayoff schedule');
            }

            $totalRequestDay = 1;

            if ($isHalfDay) {
                $totalRequestDay = 0.5;

                list($start, $end) = self::setStartEndTime($request->start_at, $request->end_at, $request->request_type, $todaySchedule->shift);

                $request->merge([
                    'start_at' => $start,
                    'end_at' => $end
                ]);
            }
        } else {
            $totalRequestDay = self::getTotalRequestDay($user, $request->start_at, $request->end_at, $request->request_type);
        }

        // check if total request day is greater than block_leave_take_days
        if ($timeoffPolicy->block_leave_take_days > 1 && $timeoffPolicy->block_leave_take_days != $totalRequestDay) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Timeoff must be taken for ' . $timeoffPolicy->block_leave_take_days . ' days');
        }

        // check if total request day is greater than max_consecutively_day
        if ($timeoffPolicy->max_consecutively_day && ($totalRequestDay > $timeoffPolicy->max_consecutively_day)) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Maximum consecutive day is ' . $timeoffPolicy->max_consecutively_day . ' days');
        }

        // check if the user has enough quota
        if ($timeoffPolicy->type->hasQuota()) {
            if ($timeoffPolicy->type->is(TimeoffPolicyType::ANNUAL_LEAVE)) {
                if (
                    self::checkTotalBalanceQuotaAL($user->id, $request->timeoff_policy_id, $totalRequestDay, $request->start_at, $request->end_at) === false
                ) {
                    throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Quota exceeded');
                }
            } else {
                $quota = self::getTotalBalanceQuota($user->id, $request->timeoff_policy_id, $request->start_at, $request->end_at);
                // check if total request day is greater than quota
                if ($totalRequestDay > $quota) {
                    throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Quota exceeded');
                }
            }
        }

        // get timeoffs request that is still in progress
        $timeoffs = Timeoff::where('user_id', $user->id)
            ->whereApprovalStatus(ApprovalStatus::ON_PROGRESS->value)
            ->whereBetweenStartEnd($request->start_at, $request->end_at)
            ->exists();

        // if there is a timeoffs request is still in progress
        if ($timeoffs) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'There is a timeoff request that is still in progress status in the date range you requested');
        }

        // check if the user has taken leave in the date range
        if ($user->attendances()->whereNotNull('timeoff_id')->whereDateBetween($request->start_at, $request->end_at)->exists()) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'You have taken leave between the dates you requested');
        }

        // check if total request day is less than 0
        if ($totalRequestDay <= 0) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Total request day must be greather than 0');
        }

        // merge total request days
        $request->merge([
            'total_days' => $totalRequestDay
        ]);

        return $request;
    }

    public static function approved(Timeoff $timeoff)
    {
        // kurangi quota yang ada di table timeoff_quotas berdasarkan timeoff_policy_id nya, order by id asc
        // record di table user_timeoff_histories
        DB::beginTransaction();
        try {
            $startDate = Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($timeoff->start_at)));
            $endDate = Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($timeoff->end_at)));
            $dateRange = CarbonPeriod::create($startDate, $endDate);
            foreach ($dateRange as $date) {
                $todaySchedule = ScheduleService::getTodaySchedule($timeoff->user, $date->format('Y-m-d'), ['id'], ['id', 'is_dayoff']);
                if (($todaySchedule || $todaySchedule->shift) && $todaySchedule->shift->is_dayoff === false) {
                    $attendance = Attendance::where('user_id', $timeoff->user_id)->whereDate('date', $date)->first();
                    if ($attendance) {
                        $attendance->update([
                            'timeoff_id' => $timeoff->id,
                            'code' => $timeoff->timeoffPolicy->code,
                        ]);
                    } else {
                        Attendance::create([
                            'user_id' => $timeoff->user_id,
                            'schedule_id' => $todaySchedule->id,
                            'shift_id' => $todaySchedule->shift->id,
                            'timeoff_id' => $timeoff->id,
                            'code' => $timeoff->timeoffPolicy->code,
                            'date' => $date,
                        ]);
                    }
                }
            }

            $totalRequestDay = self::getTotalRequestDay($timeoff->user, $timeoff->start_at, $timeoff->end_at, $timeoff->request_type);

            self::applyTimeoffQuota($timeoff, $totalRequestDay);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
        }
    }

    /**
     * Apply timeoff quota based on the given timeoff and total request day
     *
     * @param Timeoff $timeoff
     * @param float $totalRequestDay
     * @return void
     */
    public static function applyTimeoffQuota(Timeoff $timeoff, float $totalRequestDay)
    {
        if ($timeoff->timeoffPolicy->type->hasQuota() && $totalRequestDay > 0) {

            if ($timeoff->timeoffPolicy->type->is(TimeoffPolicyType::ANNUAL_LEAVE)) {
                $timeoffQuota = $timeoff->user->timeoffQuotas()->where('timeoff_policy_id', $timeoff->timeoff_policy_id)->whereActive($timeoff->start_at, $timeoff->end_at)->orderBy('effective_end_date')->first();

                if ($timeoffQuota) {
                    $oldBalance = $timeoffQuota->balance;
                    // Check if the quota is exceeded
                    // if (($timeoffQuota->used_quota + $totalRequestDay) > $oldBalance) {
                    if ($totalRequestDay > $oldBalance) {
                        // $usedQuota = $timeoffQuota->quota - $timeoffQuota->used_quota;
                        $timeoffQuota->used_quota += $oldBalance;
                        $totalRequestDay -= $oldBalance;
                    } else {
                        // If quota is not exceeded, update the used quota
                        $timeoffQuota->used_quota += $totalRequestDay;
                        $totalRequestDay = 0;
                    }

                    $timeoffQuota->save();

                    // Create a new record in the user_timeoff_histories table
                    $timeoffQuotaHistory = $timeoffQuota->timeoffQuotaHistories()->create([
                        'user_id' => $timeoff->user->id,
                        'is_increment' => false,
                        'old_balance' => $oldBalance,
                        'new_balance' => $timeoffQuota->quota - $timeoffQuota->used_quota,
                    ]);
                    // dd(array_merge($timeoff->timeoff_quota_histories ?? [], [$timeoffQuotaHistory->toArray()]));
                    $timeoff->timeoff_quota_histories = array_merge($timeoff->timeoff_quota_histories ?? [], [$timeoffQuotaHistory->toArray()]);
                    $timeoff->saveQuietly();

                    // Recursively call the function if the total request day is still greater than 0
                    if ($totalRequestDay > 0) self::applyTimeoffQuota($timeoff, $totalRequestDay);
                } else {
                    $timeoffQuotas = $timeoff->user->timeoffQuotas()->where('timeoff_policy_id', $timeoff->timeoff_policy_id)->whereActive(date('Y-m-d', strtotime($timeoff->start_at . '+3 month')), date('Y-m-d', strtotime($timeoff->end_at . '+3 month')))->orderBy('effective_end_date')->get();

                    foreach ($timeoffQuotas as $timeoffQuota) {
                        // Check if the quota is exceeded
                        $oldBalance = $timeoffQuota->balance;
                        if ($totalRequestDay > $oldBalance) {
                            // $usedQuota = $timeoffQuota->quota - $timeoffQuota->used_quota;
                            $timeoffQuota->used_quota += $oldBalance;
                            $totalRequestDay -= $oldBalance;
                        } else {
                            // If quota is not exceeded, update the used quota
                            $timeoffQuota->used_quota += $totalRequestDay;
                            $totalRequestDay = 0;
                        }

                        $timeoffQuota->save();

                        // Create a new record in the user_timeoff_histories table
                        $timeoffQuotaHistory = $timeoffQuota->timeoffQuotaHistories()->create([
                            'user_id' => $timeoff->user->id,
                            'is_increment' => false,
                            'old_balance' => $oldBalance,
                            'new_balance' => $timeoffQuota->quota - $timeoffQuota->used_quota,
                        ]);

                        $timeoff->timeoff_quota_histories = array_merge($timeoff->timeoff_quota_histories ?? [], [$timeoffQuotaHistory->toArray()]);
                        $timeoff->saveQuietly();

                        if ($totalRequestDay <= 0) break;
                    }
                }
            } else {
                $timeoffQuota = $timeoff->user->timeoffQuotas()->where('timeoff_policy_id', $timeoff->timeoff_policy_id)->whereActive($timeoff->start_at, $timeoff->end_at)->orderBy('effective_end_date')->first();

                $oldBalance = $timeoffQuota->balance;
                // Check if the quota is exceeded
                if ($totalRequestDay > $oldBalance) {
                    // $usedQuota = $timeoffQuota->quota - $timeoffQuota->used_quota;
                    $timeoffQuota->used_quota += $oldBalance;
                    $totalRequestDay -= $oldBalance;
                } else {
                    // If quota is not exceeded, update the used quota
                    $timeoffQuota->used_quota += $totalRequestDay;
                    $totalRequestDay = 0;
                }

                $timeoffQuota->save();

                // Create a new record in the user_timeoff_histories table
                $timeoffQuotaHistory = $timeoffQuota->timeoffQuotaHistories()->create([
                    'user_id' => $timeoff->user->id,
                    'is_increment' => false,
                    'old_balance' => $oldBalance,
                    'new_balance' => $timeoffQuota->quota - $timeoffQuota->used_quota,
                ]);

                $timeoff->timeoff_quota_histories = array_merge($timeoff->timeoff_quota_histories ?? [], [$timeoffQuotaHistory->toArray()]);
                $timeoff->saveQuietly();

                // Recursively call the function if the total request day is still greater than 0
                if ($totalRequestDay > 0) self::applyTimeoffQuota($timeoff, $totalRequestDay);
            }
        }
    }

    public static function setStartEndTime(string $startAt, string $endAt, string $requestType, Shift $shift)
    {
        $startAtDate = date('Y-m-d', strtotime($startAt));
        $endAtDate = date('Y-m-d', strtotime($endAt));
        $interval = ceil(ShiftService::getIntervalHours($shift) / 2);
        if ($requestType == TimeoffRequestType::HALF_DAY_BEFORE_BREAK->value) {
            $startAt = date('Y-m-d H:i:s', strtotime($startAtDate . $shift->clock_in));
            $endAt = date('Y-m-d H:i:s', strtotime($startAt . '+' . $interval . ' hours'));
        } else {
            $endAt = date('Y-m-d H:i:s', strtotime($endAtDate . $shift->clock_out));
            $startAt = date('Y-m-d H:i:s', strtotime($endAt . '-' . $interval . ' hours'));
        }

        return [
            $startAt,
            $endAt
        ];
    }
}
