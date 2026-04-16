<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LockAttendance\SearchRequest;
use App\Http\Requests\Api\LockAttendance\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\LockAttendance\LockAttendanceServiceInterface;
use App\Models\Event;
use App\Models\LockAttendance;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ScheduleService;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class LockAttendanceController extends BaseController
{
    public function __construct(private LockAttendanceServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        Gate::authorize('viewAny', LockAttendance::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            null,
            [
                AllowedFilter::exact('company_id'),
                'start_date',
                'end_date',
            ],
            [],
            [
                'id',
                'company_id',
                'start_date',
                'end_date',
                'created_at',
            ],
            [
                'id',
                'company_id',
                'start_date',
                'end_date',
                'created_at',
            ],
        );

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', LockAttendance::class);

        $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(StoreRequest $request, int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function details(SearchRequest $request, int $id)
    {
        $lockAttendance = LockAttendance::findTenanted($id);

        $search = $request->filter['search'] ?? null;
        $users = User::select('id', 'name', 'nik')
            ->where('company_id', $lockAttendance->company_id)
            ->when($search, fn($q) => $q->where(fn($q) => $q->whereLike('name', $search)->orWhereLike('nik', $search)))
            ->with('payrollInfo', fn($q) => $q->select('user_id', 'is_ignore_alpa', 'total_working_days'))
            ->paginate($this->per_page);

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
            $summaryAwayTimeOff = 0;

            $attendances = AttendanceService::getUserAttendancesInPeriod($user, $lockAttendance->start_date, $lockAttendance->end_date, [
                'shift' => fn($q) => $q->select('id', 'is_dayoff', 'clock_in', 'clock_out')->withTrashed(),
                'timeoff' => fn($q) => $q->select('id', 'request_type')->approved(),
                'clockIn' => fn($q) => $q->select('id', 'attendance_id', 'time')->approved(),
                'clockOut' => fn($q) => $q->select('id', 'attendance_id', 'time')->approved(),
            ]);

            foreach ($dateRange as $date) {
                // 1. get schedule in date
                $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name', 'is_overide_national_holiday', 'is_overide_company_holiday', 'effective_date'], ['id', 'is_dayoff', 'name', 'clock_in', 'clock_out']);

                // 2. if doesnt have schedule, skip
                if (!$schedule || !$schedule->shift) {
                    continue;
                }

                $date = $date->format('Y-m-d');
                $attendance = $attendances->firstWhere('date', $date);

                // 3. if dayoff, skip
                if ($schedule->shift->is_dayoff) {
                    continue;
                };

                $isHoliday = false;
                if ($schedule?->is_overide_company_holiday == false) {
                    $isHoliday = $companyHolidays->contains(function ($ch) use ($date) {
                        return date('Y-m-d', strtotime($ch->start_at)) <= $date && date('Y-m-d', strtotime($ch->end_at)) >= $date;
                    });

                    if ($isHoliday) {
                        continue;
                    }
                }

                if ($schedule?->is_overide_national_holiday == false && !$isHoliday) {
                    $isHoliday = $nationalHolidays->contains(function ($nh) use ($date) {
                        return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
                    });

                    if ($isHoliday) {
                        continue;
                    }
                }

                if ($attendance) {
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
                    if ($attendance->timeoff && $attendance->timeoff->request_type->is(\App\Enums\TimeoffRequestType::FULL_DAY)) {
                        $summaryAwayTimeOff += 1;
                    }
                    // } else {
                    //     if ($user->payrollInfo?->is_ignore_alpa == false && (!$attendance->clockIn || !$attendance->clockOut)) {
                    //         $summaryNotPresentAbsent += 1;
                    //     }
                }

                if ($user->payrollInfo?->is_ignore_alpa == false && (!$attendance || !$attendance?->clockIn || !$attendance?->clockOut)) {
                    if ($attendance?->timeoff) continue;
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
            $user->summaryAwayTimeOff = $summaryAwayTimeOff;
        });

        return DefaultResource::collection($users);
    }
}
