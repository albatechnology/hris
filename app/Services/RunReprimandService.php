<?php

namespace App\Services;

use App\Enums\TimeoffRequestType;
use App\Http\Requests\Api\RunReprimand\StoreRequest;
use App\Models\Attendance;
use App\Models\RunReprimand;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\DB;

class RunReprimandService
{

    public function store(StoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $runReprimand = RunReprimand::create($request->validated());

            $this->createReprimand($request);

            return $runReprimand;
        });
    }

    /**
     * Handle the creation of reprimands for users.
     *
     * @param object{company_id: int, start_date: string, end_date: string, user_ids?: string} $request
     */
    private function createReprimand(StoreRequest $request)
    {
        $userIds = $request->user_ids ? explode(',', $request->user_ids) : null;

        $users = User::select('id', 'name', 'join_date')
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->where('company_id', $request->company_id)
            ->get();


        $dateRange = CarbonPeriod::create($request->start_date, $request->end_date);

        foreach ($users as $user) {
            // hitung telat di range start_date - end_date
            $attendances = Attendance::select('id', 'shift_id', 'date', 'timeoff_id')->where('user_id', $user->id)
                ->whereDateBetween($request->start_date, $request->end_date)
                ->where(function ($q) {
                    $q->whereNull('timeoff_id')
                        ->orWhereHas('timeoff', fn($q) => $q->where('request_type', '!=', TimeoffRequestType::FULL_DAY));
                })
                ->withWhereHas('clockIn', fn($q) => $q->approved()->select('attendance_id', 'time', 'is_clock_in'))
                ->withWhereHas('clockOut', fn($q) => $q->approved()->select('attendance_id', 'time', 'is_clock_in'))
                ->withWhereHas('shift', fn($q) => $q->withTrashed()->where('is_dayoff', 0)->selectMinimalist(['is_enable_grace_period', 'time_dispensation']))
                ->get();

            $total = array_reduce($dateRange->toArray(), function (?int $carry, Carbon $date) use ($attendances) {
                $attendance = $attendances->firstWhere('date', $date->format('Y-m-d'));

                if ($attendance) {

                    // Clock in
                    list($diffInMinute, $diffInTime, $remainingTime) = AttendanceService::getTotalLateTime($attendance->clockIn, $attendance->shift);


                    // Clock out
                    list($diffInMinute, $diffInTime, $remainingTime) = AttendanceService::getTotalLateTime($attendance->clockOut, $attendance->shift, $remainingTime);

                    if ($attendance->clockIn) {
                        list($diffInMinute, $diffInTime, $remainingTime) = AttendanceService::getTotalLateTime($attendance->clockIn, $attendance->shift, $diffInMinute);
                    }

                    return $carry + $remainingTime;
                }

                return $carry + 0;
            });

            $workingMonth = UserService::getWorkingMonth($user);

            dump($workingMonth);
            dd($total);
        }
    }
}
