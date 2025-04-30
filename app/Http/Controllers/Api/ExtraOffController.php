<?php

namespace App\Http\Controllers\Api;

use App\Enums\TimeoffPolicyType;
use App\Http\Requests\Api\ExtraOff\EligibleUsersRequest;
use App\Http\Requests\Api\ExtraOff\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\ExtraOff;
use App\Models\TimeoffPolicy;
use App\Models\TimeoffQuota;
use App\Models\TimeoffQuotaHistory;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExtraOffController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:extra_off_access', ['only' => ['restore']]);
        $this->middleware('permission:extra_off_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:extra_off_create', ['only' => 'store']);
        // $this->middleware('permission:extra_off_edit', ['only' => 'update']);
        // $this->middleware('permission:extra_off_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function eligibleUsers(EligibleUsersRequest $request)
    {
        $today = now()->startOfDay();
        if ($request->date) {
            $today = Carbon::parse($request->date)->startOfDay();
        }


        $startDate = $today->copy()->subMonths(4);
        // $startDate = $today->copy()->subWeek();
        if ($request->days) {
            $startDate = $today->copy()->subDays($request->days);
        }

        $dateRange = CarbonPeriod::create($startDate, $today);

        $users = User::select('id', 'name', 'nik', 'company_id', 'branch_id', 'join_date')
            ->with('branch', fn($q) => $q->select('id', 'name'))
            ->tenanted()->get();

        /**
         * 1. join_date > 4 month from now
         * 2. not yet get extra off in 4 month
         *
         *
         * 1. no alpa
         * 2. max late 10 min
         * 3. no sick timeoff
         * 4.
         */
        $selectedUsers = [];
        foreach ($users as $user) {
            if ($startDate->lessThan($user->join_date)) continue;

            $fail = TimeoffQuotaHistory::where('user_id', $user->id)
                ->where('is_automatic', true)
                ->whereDate('created_at', '>=', $startDate->format('Y-m-d'))
                ->whereDate('created_at', '<=', $today->format('Y-m-d'))
                ->whereHas('timeoffQuota.timeoffPolicy', fn($q) => $q->where('type', TimeoffPolicyType::EXTRA_OFF))
                ->exists();
            if ($fail) continue;

            $attendances = Attendance::where('user_id', $user->id)
                ->whereDate('date', '>=', $startDate->format('Y-m-d'))
                ->whereDate('date', '<=', $today->format('Y-m-d'))
                ->with([
                    'clockIn' => fn($q) => $q->approved()->select('id', 'attendance_id', 'is_clock_in', 'time'),
                    'clockOut' => fn($q) => $q->approved()->select('id', 'attendance_id', 'is_clock_in', 'time'),
                ])
                ->get(['id', 'date']);

            $totalLate = 0;
            $isContinue = false;
            foreach ($dateRange as $date) {
                $graceTotalLate = 0;
                $todaySchedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name'], ['id', 'name', 'is_dayoff', 'clock_in', 'clock_out']);

                if (!$todaySchedule?->shift) {
                    $isContinue = true;
                    break;
                }

                if ($todaySchedule?->shift->is_dayoff) continue;

                $nationalHoliday = Event::whereNationalHoliday()
                    ->where('company_id', $user->company_id)
                    ->where(
                        fn($q) => $q->whereDate('start_at', '<=', $date->format('Y-m-d'))
                            ->whereDate('end_at', '>=', $date->format('Y-m-d'))
                    )
                    ->exists();
                if ($nationalHoliday) continue;

                $attendanceOnDate = $attendances->firstWhere('date', $date->format('Y-m-d'));
                // $attendanceOnDate = $attendances->firstWhere('date', '2025-02-12');
                if (!$attendanceOnDate) {
                    $isContinue = true;
                    break;
                }

                if (!$attendanceOnDate->clockIn || !$attendanceOnDate->clockOut) {
                    $isContinue = true;
                    break;
                }

                $attendanceClockIn = Carbon::parse($attendanceOnDate->clockIn->time);
                $scheduleClockIn = Carbon::parse($attendanceClockIn->format('Y-m-d') . ' ' . $todaySchedule->shift->clock_in);
                if ($attendanceClockIn->greaterThan($scheduleClockIn)) {
                    $time = $attendanceClockIn->diffInMinutes($scheduleClockIn);
                    $graceTotalLate += $time;
                    if ($graceTotalLate > 10) {
                        $totalLate += $time - 10;
                    }
                }

                if ($totalLate > 10) {
                    $isContinue = true;
                    break;
                }

                $attendanceClockOut = Carbon::parse($attendanceOnDate->clockOut->time);
                $scheduleClockOut = Carbon::parse($attendanceClockOut->format('Y-m-d') . ' ' . $todaySchedule->shift->clock_out);
                if ($attendanceClockOut->lessThan($scheduleClockOut)) {
                    $time = $attendanceClockOut->diffInMinutes($scheduleClockOut);
                    $graceTotalLate += $time;
                    if ($graceTotalLate > 10) {
                        $totalLate += $time - max(10 - $graceTotalLate, 0);
                    }
                }

                if ($totalLate > 10) {
                    $isContinue = true;
                    break;
                }
            }

            if ($isContinue) continue;

            $selectedUsers[] = $user;
        }

        return DefaultResource::collection($selectedUsers);
    }

    public function index()
    {
        $data = QueryBuilder::for(ExtraOff::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::scope('start_effective_date'),
                AllowedFilter::scope('end_effective_date'),
                AllowedFilter::callback('has_quota', function ($query, bool $value) {
                    if ($value == true) {
                        $query->whereIn('type', TimeoffPolicyType::hasQuotas());
                    }
                }),
                'type',
                'name',
                'code',
                'is_allow_halfday',
                'is_for_all_user',
                'is_enable_block_leave',
                'is_unlimited_day',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'effective_date',
                'expired_date',
                'type',
                'name',
                'code',
                'is_allow_halfday',
                'is_for_all_user',
                'is_enable_block_leave',
                'is_unlimited_day',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $data = ExtraOff::findTenanted($id);

        $users = User::select('id', 'name', 'nik', 'branch_id')
            ->with('branch', fn($q) => $q->select('id', 'name'))
            ->whereIn('id', $data->user_ids)
            ->get();

        $data->users = $users;

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        $userIds = array_values($request->user_ids);
        $description = "Extra off quota added by " . auth()->user()->name;
        $timeoffPolicyId = TimeoffPolicy::tenanted()->where('type', TimeoffPolicyType::EXTRA_OFF)->firstOrFail(['id'])->id;

        DB::beginTransaction();
        try {
            $extraOff = ExtraOff::create($request->validated());

            foreach ($userIds as $userId) {
                $timeoffQuota = TimeoffQuota::create([
                    'timeoff_policy_id' => $timeoffPolicyId,
                    'user_id' => $userId,
                    'effective_start_date' => date('Y-m-d'),
                    'quota' => 1,
                ]);

                $timeoffQuota->timeoffQuotaHistories()->create([
                    'user_id' => $timeoffQuota->user_id,
                    'is_increment' => true,
                    'is_automatic' => true,
                    'new_balance' => $timeoffQuota->quota,
                    'description' => $description,
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($extraOff);
    }

    // public function update(int $id, StoreRequest $request)
    // {
    //     $extraOff = ExtraOff::findTenanted($id);
    //     try {
    //         $extraOff->update($request->validated());

    //         if ($request->user_ids && count($request->user_ids) > 0) {
    //             $extraOff->users()->sync($request->user_ids);
    //         }
    //     } catch (Exception $e) {
    //         return $this->errorResponse($e->getMessage());
    //     }

    //     return (new DefaultResource($extraOff))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    // }

    // public function destroy(int $id)
    // {
    //     $extraOff = ExtraOff::findTenanted($id);
    //     $extraOff->delete();

    //     return $this->deletedResponse();
    // }

    // public function forceDelete(int $id)
    // {
    //     $extraOff = ExtraOff::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
    //     $extraOff->forceDelete();

    //     return $this->deletedResponse();
    // }

    // public function restore(int $id)
    // {
    //     $extraOff = ExtraOff::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
    //     $extraOff->restore();

    //     return new DefaultResource($extraOff);
    // }
}
