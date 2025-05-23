<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LockAttendance\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\LockAttendance;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\CarbonPeriod;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LockAttendanceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:lock_attendance_access', ['only' => ['restore']]);
        $this->middleware('permission:lock_attendance_read', ['only' => ['index', 'show', 'details']]);
        $this->middleware('permission:lock_attendance_create', ['only' => 'store']);
        $this->middleware('permission:lock_attendance_edit', ['only' => 'update']);
        $this->middleware('permission:lock_attendance_delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(LockAttendance::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'start_date',
                'end_date',
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'start_date',
                'end_date',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $lockAttendance = LockAttendance::findTenanted($id);
        return new DefaultResource($lockAttendance);
    }

    public function store(StoreRequest $request)
    {
        $lockAttendance = LockAttendance::create($request->validated());

        return new DefaultResource($lockAttendance);
    }

    public function update(int $id, StoreRequest $request)
    {
        $lockAttendance = LockAttendance::findTenanted($id);
        $lockAttendance->update($request->validated());

        return (new DefaultResource($lockAttendance))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $lockAttendance = LockAttendance::findTenanted($id);
        $lockAttendance->delete();

        return $this->deletedResponse();
    }

    public function details(int $id)
    {
        $lockAttendance = LockAttendance::findTenanted($id);

        $users = User::select('id', 'name', 'nik')
            ->with('payrollInfo', fn($q) => $q->select('user_id', 'total_working_days'))
            ->where('company_id', $lockAttendance->company_id)->paginate($this->per_page);

        $dateRange = CarbonPeriod::create($lockAttendance->start_date, $lockAttendance->end_date);
        $companyHolidays = Event::selectMinimalist()->whereCompany($lockAttendance->company_id)->whereDateBetween($lockAttendance->start_date, $lockAttendance->end_date)->whereCompanyHoliday()->get();
        $nationalHolidays = Event::selectMinimalist()->whereCompany($lockAttendance->company_id)->whereDateBetween($lockAttendance->start_date, $lockAttendance->end_date)->whereNationalHoliday()->get();

        $users->map(function ($user) use ($lockAttendance, $dateRange, $companyHolidays, $nationalHolidays) {
            $summaryPresentAbsent = 0;
            $summaryPresentOnTime = 0;
            $summaryPresentLateClockIn = 0;
            $summaryPresentEarlyClockOut = 0;
            $summaryNotPresentAbsent = 0;
            $summaryNotPresentNoClockIn = 0;
            $summaryNotPresentNoClockOut = 0;
            // $summaryAwayDayOff = 0;
            $summaryAwayTimeOff = 0;

            $attendances = Attendance::where('user_id', $user->id)
                ->where(fn($q) => $q->whereHas('details', fn($q) => $q->approved())->orHas('timeoff'))
                ->with([
                    'shift' => fn($q) => $q->select('id', 'is_dayoff', 'clock_in', 'clock_out')->withTrashed(),
                    'timeoff' => fn($q) => $q->select('id')->approved(),
                    'clockIn' => fn($q) => $q->select('id', 'attendance_id', 'time')->approved(),
                    'clockOut' => fn($q) => $q->select('id', 'attendance_id', 'time')->approved(),
                    // 'details' => fn($q) => $q->approved()->orderBy('created_at')
                ])
                ->whereDateBetween($lockAttendance->start_date, $lockAttendance->end_date)
                ->get();

            foreach ($dateRange as $date) {
                $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name', 'is_overide_national_holiday', 'is_overide_company_holiday', 'effective_date'], ['id', 'is_dayoff', 'name', 'clock_in', 'clock_out']);

                // 1. kalo tgl merah(national holiday), shift nya pake tgl merah
                // 2. kalo company event(holiday), shiftnya pake holiday
                // 3. kalo schedulenya is_overide_national_holiday == false, shiftnya pake shift
                // 4. kalo schedulenya is_overide_company_holiday == false, shiftnya pake shift
                // 5. kalo ngambil timeoff, shfitnya tetap pake shift hari itu, munculin data timeoffnya
                $date = $date->format('Y-m-d');
                $attendance = $attendances->firstWhere('date', $date);

                $isHoliday = false;
                if ($schedule?->is_overide_company_holiday == false) {
                    $isHoliday = $companyHolidays->contains(function ($ch) use ($date) {
                        return date('Y-m-d', strtotime($ch->start_at)) <= $date && date('Y-m-d', strtotime($ch->end_at)) >= $date;
                    });
                }

                if ($schedule?->is_overide_national_holiday == false && !$isHoliday) {
                    $isHoliday = $nationalHolidays->contains(function ($nh) use ($date) {
                        return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
                    });
                }

                if ($attendance && !$isHoliday) {
                    $shift = $attendance->shift;

                    if ($attendance->clockIn) {
                        $shiftClockInTime = strtotime($shift->clock_in);
                        $clockInTime = strtotime(date('H:i:s', strtotime($attendance->clockIn->time)));

                        // calculate present on time (include early clock in)
                        if ($clockInTime <= $shiftClockInTime) {
                            $summaryPresentOnTime += 1;
                        }

                        // calculate late clock in
                        if ($clockInTime > $shiftClockInTime) {
                            $summaryPresentLateClockIn += 1;
                        }

                        // calculate if no clock out but clock in
                        if (!$attendance->clockOut) {
                            $summaryNotPresentNoClockOut += 1;
                        }
                    }

                    if ($attendance->clockOut) {
                        $shiftClockOutTime = strtotime($shift->clock_out);
                        $clockOutTime = strtotime(date('H:i:s', strtotime($attendance->clockOut->time)));

                        // calculate early clock out
                        if ($clockOutTime < $shiftClockOutTime) {
                            $summaryPresentEarlyClockOut += 1;
                        }

                        // calculate if no clock in but clock out
                        if (!$attendance->clockIn) {
                            $summaryNotPresentNoClockIn += 1;
                        }
                    }

                    if ($attendance->clockIn && $attendance->clockOut) {
                        $summaryPresentAbsent += 1;
                    }

                    // calculate timeoff
                    if ($attendance->timeoff) {
                        $summaryAwayTimeOff += 1;
                    }
                } else {
                    $summaryNotPresentAbsent += 1;
                }
            }

            $user->summaryPresentAbsent = $summaryPresentAbsent;
            $user->summaryPresentOnTime = $summaryPresentOnTime;
            $user->summaryPresentLateClockIn = $summaryPresentLateClockIn;
            $user->summaryPresentEarlyClockOut = $summaryPresentEarlyClockOut;
            $user->summaryNotPresentAbsent = $summaryNotPresentAbsent;
            $user->summaryNotPresentNoClockIn = $summaryNotPresentNoClockIn;
            $user->summaryNotPresentNoClockOut = $summaryNotPresentNoClockOut;
            // // $user->summaryAwayDayOff = $summaryAwayDayOff;
            $user->summaryAwayTimeOff = $summaryAwayTimeOff;
        });

        return DefaultResource::collection($users);
    }
}
