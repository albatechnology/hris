<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\TimeoffRequestType;
use App\Http\Requests\Api\Timeoff\StoreRequest;
use App\Models\Attendance;
use App\Models\Timeoff;
use App\Models\TimeoffPolicy;
use App\Models\TimeoffQuota;
use App\Models\User;
use DateTime;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TimeoffService
{
    public static function getTotalBalanceQuota(int|string $userId, int|string $timeoffPolicyId, ?string $startAt = null, ?string $endAt = null): float
    {
        $timeoffQuota = TimeoffQuota::where('user_id', $userId)->where('timeoff_policy_id', $timeoffPolicyId)->whereActive($startAt, $endAt)->groupBy('timeoff_policy_id')->first([DB::raw('SUM(quota) as total_quota'), DB::raw('SUM(used_quota) as total_used_quota')]);

        if (!$timeoffQuota) return 0;

        return floatval($timeoffQuota->total_quota - $timeoffQuota->total_used_quota);
    }

    public static function getTotalRequestDay(string $startDate, string $endDate, TimeoffRequestType|string $timeoffRequestType): float
    {
        $value = 0.5;
        if (!($timeoffRequestType instanceof TimeoffRequestType)) {
            $timeoffRequestType = TimeoffRequestType::from($timeoffRequestType);
        }

        if ($timeoffRequestType->is(TimeoffRequestType::FULL_DAY)) {
            $startDate = new DateTime(date('Y-m-d', strtotime($startDate)));
            $endDate = new DateTime(date('Y-m-d', strtotime($endDate)));
            if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
                $value = 1;
            } else {
                $interval = $startDate->diff($endDate);
                $value = $interval->days + 1;
            }
        }
        return $value;
    }

    public static function requestTimeoffValidation(StoreRequest $request): void
    {
        // PR
        // 1. ketika timeoff approved tapi quotanya ga ada, Attendance nya ga kebuat, tapi statusnya approved

        // VALIDATION
        // 1. cek apakah ditanggal request, user tsb punya schedule
        // 2. cek type timeoff nya
        // 3. untuk timeoff type (SICK_WITH_CERTIFICATE, FREE_LEAVE, UNPAID_LEAVE, PREGNANCY_LEAVE) tidak perlu cek quota, tergantung approvalnya aja nanti
        // 4. kalo punya quota, cek ke table timeoff_quotas
        // 5. cek apakah di range tanggal request terdapat cuti. kalo ada gabisa request

        $user = User::findOrFail($request->user_id);
        if (!ScheduleService::checkAvailableSchedule(user: $user, startDate: $request->start_at, endDate: $request->end_at)) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Schedule is not available');
        }

        if (date('Y-m-d', strtotime($request->start_at)) === date('Y-m-d', strtotime($request->end_at))) {
            $todaySchedule = ScheduleService::getTodaySchedule($user, $request->start_at);
            if (!$todaySchedule || !$todaySchedule->shift) {
                throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Schedule is not available');
            }

            if ($todaySchedule->shift->is_dayoff === true) {
                throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Cannot request timeoff in dayoff schedule');
            }
        }

        $timeoffPolicy = TimeoffPolicy::findOrFail($request->timeoff_policy_id);
        $totalRequestDay = self::getTotalRequestDay($request->start_at, $request->end_at, $request->request_type);
        if ($timeoffPolicy->block_leave_take_days > 1 && $timeoffPolicy->block_leave_take_days != $totalRequestDay) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Timeoff must be taken for ' . $timeoffPolicy->block_leave_take_days . ' days');
        }

        if ($timeoffPolicy->max_consecutively_day && ($totalRequestDay > $timeoffPolicy->max_consecutively_day)) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Maximum consecutive day is ' . $timeoffPolicy->max_consecutively_day . ' days');
        }

        if ($timeoffPolicy->type->hasQuota()) {
            $quota = self::getTotalBalanceQuota($user->id, $request->timeoff_policy_id, $request->start_at, $request->end_at);

            if ($totalRequestDay > $quota) {
                throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Quota exceeded');
            }
        }

        $timeoffs = Timeoff::where('user_id', $user->id)
            ->whereApprovalStatus(ApprovalStatus::ON_PROGRESS->value)
            ->whereBetweenStartEnd($request->start_at, $request->end_at)
            ->exists();
        if ($timeoffs) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'There is a timeoff request that is still in progress status in the date range you requested');
        }

        if ($user->attendances()->whereDateBetween($request->start_at, $request->end_at)->exists()) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'You have taken leave between the dates you requested');
        }
    }

    public static function approved(Timeoff $timeoff)
    {
        // $intervalDays = date_diff(new DateTime($timeoff->start_at), new DateTime($timeoff->end_at))->days;
        // if ($intervalDays > 1) {
        //     $dateRange = new \DatePeriod(date_create($timeoff->start_at), new \DateInterval('P1D'), date_create($timeoff->end_at));

        //     foreach ($dateRange as $date) {
        //         $schedule = ScheduleService::getTodaySchedule($timeoff->user, $date->format('Y-m-d'));
        //         Attendance::create([
        //             'user_id' => $timeoff->user_id,
        //             'schedule_id' => $schedule->id,
        //             'shift_id' => $schedule->shift->id,
        //             'timeoff_id' => $timeoff->id,
        //             'code' => $timeoff->timeoffPolicy->code,
        //             'date' => $date->format('Y-m-d'),
        //         ]);
        //     }
        // } else {
        //     $schedule = ScheduleService::getTodaySchedule($timeoff->user, $timeoff->start_at);
        //     Attendance::create([
        //         'user_id' => $timeoff->user_id,
        //         'schedule_id' => $schedule->id,
        //         'shift_id' => $schedule->shift->id,
        //         'timeoff_id' => $timeoff->id,
        //         'code' => $timeoff->timeoffPolicy->code,
        //         'date' => $timeoff->start_at,
        //     ]);
        // }

        // kurangi quota yang ada di table timeoff_quotas berdasarkan timeoff_policy_id nya, order by id asc
        // record di table user_timeoff_histories
        DB::beginTransaction();
        try {
            $intervalDays = date_diff(new DateTime(date('Y-m-d', strtotime($timeoff->start_at))), new DateTime(date('Y-m-d', strtotime($timeoff->end_at))))->days + 1;
            $addDay = 0;
            $i = 0;
            while ($i < $intervalDays) {
                $date = date('Y-m-d', strtotime($timeoff->start_at . ' + ' . $addDay++ . ' days'));
                $schedule = ScheduleService::getTodaySchedule($timeoff->user, $date);
                if (!$schedule || $schedule->shift->is_dayoff == true) {
                    continue;
                }

                if ($timeoff->user->attendances()->where('date', $date)->exists()) continue;

                Attendance::create([
                    'user_id' => $timeoff->user_id,
                    'schedule_id' => $schedule->id,
                    'shift_id' => $schedule->shift->id,
                    'timeoff_id' => $timeoff->id,
                    'code' => $timeoff->timeoffPolicy->code,
                    'date' => $date,
                ]);
                $i++;
            }

            $totalRequestDay = TimeoffService::getTotalRequestDay($timeoff->start_at, $timeoff->end_at, $timeoff->request_type);

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
            $timeoffQuota = $timeoff->user->timeoffQuotas()->where('timeoff_policy_id', $timeoff->timeoff_policy_id)->whereActive()->first();

            // Check if the quota is exceeded
            if (($timeoffQuota->used_quota + $totalRequestDay) > $timeoffQuota->quota) {
                $usedQuota = $timeoffQuota->quota - $timeoffQuota->used_quota;
                $timeoffQuota->used_quota += $usedQuota;
                $totalRequestDay -= $usedQuota;
            } else {
                // If quota is not exceeded, update the used quota
                $timeoffQuota->used_quota += $totalRequestDay;
                $totalRequestDay = 0;
            }

            $timeoffQuota->save();

            // Create a new record in the user_timeoff_histories table
            $timeoffQuota->timeoffQuotaHistories()->create([
                'user_id' => $timeoff->user->id,
                'is_increment' => false,
                'old_balance' => $timeoffQuota->quota,
                'new_balance' => $timeoffQuota->quota - $timeoffQuota->used_quota,
            ]);

            // Recursively call the function if the total request day is still greater than 0
            if ($totalRequestDay > 0) self::applyTimeoffQuota($timeoff, $totalRequestDay);
        }
    }
}
