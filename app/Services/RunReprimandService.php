<?php

namespace App\Services;

use App\Enums\TimeoffRequestType;
use App\Http\Requests\Api\RunReprimand\StoreRequest;
use App\Models\Attendance;
use App\Models\RunReprimand;
use App\Models\User;
use App\Enums\ReprimandType;
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

            $results = $this->createReprimand($request);

            return [
                'run' => $runReprimand,
                'results' => $results,
            ];
        });
    }

    /**
     * Handle the creation of reprimands for users.
     *
     * @param object{company_id: int, start_date: string, end_date: string, user_ids?: string} $request
     */
    public function createReprimand(StoreRequest $request): array
    {
        $userIds = $request->user_ids ? explode(',', $request->user_ids) : null;
        // dd($userIds);
        $users = User::select('id', 'name', 'join_date')
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->where('company_id', $request->company_id)
            ->get();

        $dateRange = CarbonPeriod::create($request->start_date, $request->end_date);

        // preload attendances grouped by user
        $attendances = Attendance::select('id', 'user_id', 'shift_id', 'date', 'timeoff_id')
            ->whereDateBetween($request->start_date, $request->end_date)
            ->where(function ($q) {
                $q->whereNull('timeoff_id')
                    ->orWhereHas('timeoff', fn($q) => $q->where('request_type', '!=', TimeoffRequestType::FULL_DAY));
            })
            ->withWhereHas('clockIn', fn($q) => $q->approved()->select('attendance_id', 'time', 'is_clock_in'))
            ->withWhereHas('clockOut', fn($q) => $q->approved()->select('attendance_id', 'time', 'is_clock_in'))
            ->withWhereHas('shift', fn($q) => $q->withTrashed()->where('is_dayoff', 0)->selectMinimalist(['is_enable_grace_period', 'time_dispensation', 'clock_in', 'clock_out']))
            ->get()
            ->groupBy('user_id');

        $results = [];

        foreach ($users as $user) {
            $userAttendances = $attendances->get($user->id) ?? collect();

            $userTotal = 0;
            $perDay = [];

            foreach ($dateRange as $date) {
                $attendance = $userAttendances->firstWhere('date', $date->format('Y-m-d'));
                if (!$attendance || !$attendance->shift) continue;

                $remaining = 0;
                $minutesIn = 0;
                $minutesOut = 0;

                if ($attendance->clockIn) {
                    list($minutesIn, $diffInTime, $remaining) = AttendanceService::getTotalLateTime($attendance->clockIn, $attendance->shift, $remaining);
                }

                if ($attendance->clockOut) {
                    list($minutesOut, $diffInTime2, $remaining) = AttendanceService::getTotalLateTime($attendance->clockOut, $attendance->shift, $remaining);
                }

                $dayTotal = ($minutesIn ?? 0) + ($minutesOut ?? 0);
                $userTotal += $dayTotal;

                $perDay[$date->format('Y-m-d')] = [
                    'in' => $minutesIn,
                    'out' => $minutesOut,
                    'total' => $dayTotal,
                ];
            }

            $results[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'total_minutes' => $userTotal,
                'details' => $perDay,
            ];
        }

        return $results;
    }

    public function allReprimand(RunReprimand $runReprimand): array
    {
        // preview-only: return calculation results without persisting
        $results = [];

        $users = User::select('id', 'name', 'join_date')
            ->where('company_id', $runReprimand->company_id)
            ->get();

        $dateRange = CarbonPeriod::create($runReprimand->start_date, $runReprimand->end_date);

        // preload attendances grouped by user
        $attendances = Attendance::select('id', 'user_id', 'shift_id', 'date', 'timeoff_id')
            ->whereDateBetween($runReprimand->start_date, $runReprimand->end_reprimand ?? $runReprimand->end_date)
            ->where(function ($q) {
                $q->whereNull('timeoff_id')
                    ->orWhereHas('timeoff', fn($q) => $q->where('request_type', '!=', TimeoffRequestType::FULL_DAY));
            })
            ->withWhereHas('clockIn', fn($q) => $q->approved()->select('attendance_id', 'time', 'is_clock_in'))
            ->withWhereHas('clockOut', fn($q) => $q->approved()->select('attendance_id', 'time', 'is_clock_in'))
            ->withWhereHas('shift', fn($q) => $q->withTrashed()->where('is_dayoff', 0)->selectMinimalist(['is_enable_grace_period', 'time_dispensation', 'clock_in', 'clock_out']))
            ->get()
            ->groupBy('user_id');

        foreach ($users as $user) {
            $userAttendances = $attendances->get($user->id) ?? collect();

            $userTotal = 0;
            $perDay = [];

            foreach ($dateRange as $date) {
                $attendance = $userAttendances->firstWhere('date', $date->format('Y-m-d'));
                if (!$attendance || !$attendance->shift) continue;

                $remaining = 0;
                $minutesIn = 0;
                $minutesOut = 0;

                if ($attendance->clockIn) {
                    list($minutesIn, $diffInTime, $remaining) = AttendanceService::getTotalLateTime($attendance->clockIn, $attendance->shift, $remaining);
                }

                if ($attendance->clockOut) {
                    list($minutesOut, $diffInTime2, $remaining) = AttendanceService::getTotalLateTime($attendance->clockOut, $attendance->shift, $remaining);
                }

                $dayTotal = ($minutesIn ?? 0) + ($minutesOut ?? 0);
                $userTotal += $dayTotal;

                $perDay[$date->format('Y-m-d')] = [
                    'in' => $minutesIn,
                    'out' => $minutesOut,
                    'total' => $dayTotal,
                ];
            }

            if ($userTotal > 10) {
                $results[] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'total_minutes' => $userTotal,
                    'details' => $perDay,
                ];
            }
        }

        return $results;
    }

    /**
     * Persist all reprimands for a given RunReprimand using LateService rules.
     * Returns created/updated rows summary.
     *
     * @param RunReprimand $runReprimand
     * @param string $rulesetKey
     * @return array
     */
    public function applyAllReprimand(RunReprimand $runReprimand, string $rulesetKey = 'month_1_violation_1'): array
    {
        $lateService = new LateService();

        return DB::transaction(function () use ($runReprimand, $lateService, $rulesetKey) {
            $results = [];

            // reuse preview to compute totals
            $preview = $this->allReprimand($runReprimand);

            foreach ($preview as $row) {
                $userId = $row['user_id'];
                $total = (int) $row['total_minutes'];

                // find matching rule
                $rule = $lateService->findRuleForMinutes($total, $rulesetKey);

                $type = $rule['type'] ?? ReprimandType::SP_1->value;

                $existing = $runReprimand->reprimands()->where('user_id', $userId)->first();
                if ($existing) {
                    $existing->update([
                        'type' => $type,
                        'notes' => 'Accumulated late minutes: ' . $total,
                        'effective_date' => $runReprimand->start_date,
                        'end_date' => $runReprimand->end_date,
                    ]);
                    $results[] = ['user_id' => $userId, 'action' => 'updated', 'type' => $type];
                } else {
                    $runReprimand->reprimands()->create([
                        'user_id' => $userId,
                        'type' => $type,
                        'effective_date' => $runReprimand->start_date,
                        'end_date' => $runReprimand->end_date,
                        'notes' => 'Accumulated late minutes: ' . $total,
                    ]);
                    $results[] = ['user_id' => $userId, 'action' => 'created', 'type' => $type];
                }
            }

            return $results;
        });
    }

    // determineReprimandType removed: mapping handled by LateService rules
}
