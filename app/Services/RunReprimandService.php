<?php

namespace App\Services;

use App\Enums\TimeoffRequestType;
use App\Http\Requests\Api\RunReprimand\StoreRequest;
use App\Models\Attendance;
use App\Models\RunReprimand;
use App\Models\User;
use App\Enums\ReprimandType;
use App\Models\Reprimand;
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

            // $results = $this->createReprimand($request);

            return [
                'run' => $runReprimand,

            ];
        });
    }

    public function allReprimand(RunReprimand $runReprimand): array
    {
        // preview-only: return calculation results without persisting
        $results = [];
        $lateService = new LateService();

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

        $currentMonth = Carbon::parse($runReprimand->start_date)->startOfMonth();

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

            $total = (int)$userTotal;

            // Tentukan stage (bulan ke-X) berdasarkan streak reprimand bulan-bulan sebelumnya
            $prevStreak = $this->getPrevConsecutiveReprimandStreak($user->id, $currentMonth);
            $stage = min($prevStreak + 1, 5);

            // Hitung violation index di bulan berjalan (biasanya 1)
            $violationIndex = $this->getCurrentMonthViolationIndex(
                $user->id,
                Carbon::parse($runReprimand->start_date),
                Carbon::parse($runReprimand->end_date)
            ) + 1;

            // Evaluasi pinalti untuk total menit ini
            $eval = $lateService->evaluatePenalty($total, $stage, $violationIndex);

            $row = [
                'user_id' => $user->id,
                'name' => $user->name,
                'total_minutes' => $total,
                'stage' => $stage,
                'violation_index' => $violationIndex,
                'ruleset_key' => $eval['ruleset_key'],
                'reprimand_type' => $eval['type'] ?? null,
                'preview_message' => $eval['message'] ?? '',
                'total_cut_leave' => $eval['cut'] ?? null,
                'details' => $perDay,
                'is_over_threshold' => $total > LateService::MINUTES_TRESHOLD,
            ];

            // Tampilkan hanya yang melampaui threshold (opsional, sesuai kebutuhan)
            if ($row['is_over_threshold'] && $row['reprimand_type']) {
                $results[] = $row;
            }
        }

        return $results;
    }

    /**
     * Persist all reprimands using evaluated rules per-user. Idempotent per-run.
     */
    public function applyAllReprimand(RunReprimand $runReprimand, string $unused = ''): array
    {
        $lateService = new LateService();

        return DB::transaction(function () use ($runReprimand, $lateService) {
            $results = [];

            // gunakan preview yang sudah menghitung stage/ruleset/type
            $preview = $this->allReprimand($runReprimand);

            // tandai run sudah dirilis
            $runReprimand->update(['status' => 'release']);

            foreach ($preview as $row) {
                $userId = $row['user_id'];
                $total = (int)$row['total_minutes'];
                $type = $row['reprimand_type'] ?? ReprimandType::SP_1->value; // fallback aman
                $notes = trim('Accumulated late minutes: ' . $total . ($row['total_cut_leave'] ? " | cut leave: {$row['total_cut_leave']}" : ''));

                $existing = $runReprimand->reprimands()->where('user_id', $userId)->first();
                if ($existing) {
                    $existing->update([
                        'type' => $type,
                        'notes' => $notes,
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
                        'notes' => $notes,
                    ]);
                    $results[] = ['user_id' => $userId, 'action' => 'created', 'type' => $type];
                }
            }

            return $results;
        });
    }

    /**
     * Hitung streak reprimand berturut-turut pada bulan-bulan sebelum $month (tidak termasuk bulan $month).
     * Streak berhenti pada bulan pertama tanpa reprimand.
     */
    private function getPrevConsecutiveReprimandStreak(int $userId, Carbon $month): int
    {
        $streak = 0;
        $cursor = $month->copy()->subMonthNoOverflow()->startOfMonth();

        // Batasi maksimal 12 iterasi untuk keamanan performa
        for ($i = 0; $i < 12; $i++) {
            $start = $cursor->copy()->startOfMonth()->toDateString();
            $end = $cursor->copy()->endOfMonth()->toDateString();

            $had = Reprimand::query()
                ->where('user_id', $userId)
                ->whereBetween('effective_date', [$start, $end])
                ->exists();

            if ($had) {
                $streak++;
                $cursor->subMonthNoOverflow();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Banyaknya reprimand user pada bulan berjalan (untuk violation index).
     */
    private function getCurrentMonthViolationIndex(int $userId, Carbon $start, Carbon $end): int
    {
        return (int) Reprimand::query()
            ->where('user_id', $userId)
            ->whereBetween('effective_date', [
                $start->copy()->startOfDay()->toDateTimeString(),
                $end->copy()->endOfDay()->toDateTimeString(),
            ])
            ->count();
    }

    // public function allReprimand(RunReprimand $runReprimand): array
    // {
    //     // preview-only: return calculation results without persisting
    //     $results = [];
    //     $rulesetKey = "month_1_violation_1";
    //     $users = User::select('id', 'name', 'join_date')
    //         ->where('company_id', $runReprimand->company_id)
    //         ->get();
    //     $reprimandCountByUser = Reprimand::
    //          select('user_id', DB::raw('COUNT(*) as total'))
    //         ->groupBy('user_id')
    //         ->pluck('total','user_id');

    //     // Hitung apakah user punya reprimand pada bulan sebelumnya (berdasarkan start_date run ini)
    //     $prevMonthStart = Carbon::parse($runReprimand->start_date)->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
    //     $prevMonthEnd = Carbon::parse($runReprimand->start_date)->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
    //     $reprimandPrevMonthByUser = Reprimand::select('user_id', DB::raw('COUNT(*) as total'))
    //         ->whereBetween('effective_date', [$prevMonthStart, $prevMonthEnd])
    //         ->groupBy('user_id')
    //         ->pluck('total', 'user_id');

    //     $dateRange = CarbonPeriod::create($runReprimand->start_date, $runReprimand->end_date);

    //     // preload attendances grouped by user
    //     $attendances = Attendance::select('id', 'user_id', 'shift_id', 'date', 'timeoff_id')
    //         ->whereDateBetween($runReprimand->start_date, $runReprimand->end_reprimand ?? $runReprimand->end_date)
    //         ->where(function ($q) {
    //             $q->whereNull('timeoff_id')
    //                 ->orWhereHas('timeoff', fn($q) => $q->where('request_type', '!=', TimeoffRequestType::FULL_DAY));
    //         })
    //         ->withWhereHas('clockIn', fn($q) => $q->approved()->select('attendance_id', 'time', 'is_clock_in'))
    //         ->withWhereHas('clockOut', fn($q) => $q->approved()->select('attendance_id', 'time', 'is_clock_in'))
    //         ->withWhereHas('shift', fn($q) => $q->withTrashed()->where('is_dayoff', 0)->selectMinimalist(['is_enable_grace_period', 'time_dispensation', 'clock_in', 'clock_out']))
    //         ->get()
    //         ->groupBy('user_id');

    //     $lateService = new LateService();

    //     foreach ($users as $user) {

    //         $userAttendances = $attendances->get($user->id) ?? collect();

    //         $userTotal = 0;
    //         $perDay = [];

    //         foreach ($dateRange as $date) {
    //             $attendance = $userAttendances->firstWhere('date', $date->format('Y-m-d'));
    //             if (!$attendance || !$attendance->shift) continue;

    //             $remaining = 0;
    //             $minutesIn = 0;
    //             $minutesOut = 0;

    //             if ($attendance->clockIn) {
    //                 list($minutesIn, $diffInTime, $remaining) = AttendanceService::getTotalLateTime($attendance->clockIn, $attendance->shift, $remaining);
    //             }

    //             if ($attendance->clockOut) {
    //                 list($minutesOut, $diffInTime2, $remaining) = AttendanceService::getTotalLateTime($attendance->clockOut, $attendance->shift, $remaining);
    //             }

    //             $dayTotal = ($minutesIn ?? 0) + ($minutesOut ?? 0);
    //             $userTotal += $dayTotal;

    //             $perDay[$date->format('Y-m-d')] = [
    //                 'in' => $minutesIn,
    //                 'out' => $minutesOut,
    //                 'total' => $dayTotal,
    //             ];
    //         }
    //         $total = (int) $userTotal;

    //         $rule = $lateService->findRuleForMinutes($total, $rulesetKey);
    //         $type = data_get($rule, 'type');
    //         $cut = data_get($rule, 'total_cut_leave');
    //         // $message = $this->generateReprimandMessage($type);
    //         $message = LateService::messageFor($type, $cut);

    //         $row = [
    //             'user_id' => $user->id,
    //             'name' => $user->name,
    //             'total_minutes' => $userTotal,
    //             'count'=>(int) ($reprimandCountByUser[$user->id] ?? 0),
    //             'details' => $perDay,
    //         ];

    //         // Tambahkan indikator apakah user pernah telat (punya reprimand) di bulan sebelumnya
    //         $hadPrevMonthReprimand = ((int) ($reprimandPrevMonthByUser[$user->id] ?? 0)) > 0;
    //         $row['is_late_prev_month_ago'] = $hadPrevMonthReprimand; // true/false
    //         // Jika butuh format string: 'ya' / 'tidak', gunakan baris di bawah ini
    //         // $row['is_late_prev_month_ago_label'] = $hadPrevMonthReprimand ? 'ya' : 'tidak';

    //         if($message !== ''){
    //             $row['reprimand_type'] = $type;
    //             $row['preview_message'] = $message;
    //         }

    //         if ($userTotal > 10) {
    //             $results[] = $row;

    //         }
    //     }

    //     return $results;
    // }



    // /**
    //  * Persist all reprimands for a given RunReprimand using LateService rules.
    //  * Returns created/updated rows summary.
    //  *
    //  * @param RunReprimand $runReprimand
    //  * @param string $rulesetKey
    //  * @return array
    //  */
    // public function applyAllReprimand(RunReprimand $runReprimand, string $rulesetKey = 'month_1_violation_1'): array
    // {
    //     $lateService = new LateService();


    //     return DB::transaction(function () use ($runReprimand, $lateService, $rulesetKey) {
    //         $results = [];

    //         // reuse preview to compute totals
    //         $preview = $this->allReprimand($runReprimand);
    //         try {
    //             $runReprimand->update(['status' => 'release']);
    //         } catch (\Throwable $th) {
    //             throw $th;
    //         }
    //         foreach ($preview as $row) {
    //             $userId = $row['user_id'];
    //             $total = (int) $row['total_minutes'];

    //             // find matching rule
    //             $rule = $lateService->findRuleForMinutes($total, $rulesetKey);

    //             $type = $rule['type'] ?? ReprimandType::SP_1->value;

    //             $existing = $runReprimand->reprimands()->where('user_id', $userId)->first();
    //             if ($existing) {
    //                 $existing->update([
    //                     'type' => $type,
    //                     'notes' => 'Accumulated late minutes: ' . $total,
    //                     'effective_date' => $runReprimand->start_date,
    //                     'end_date' => $runReprimand->end_date,
    //                 ]);
    //                 $results[] = ['user_id' => $userId, 'action' => 'updated', 'type' => $type];
    //             } else {
    //                 $runReprimand->reprimands()->create([
    //                     'user_id' => $userId,
    //                     'type' => $type,
    //                     'effective_date' => $runReprimand->start_date,
    //                     'end_date' => $runReprimand->end_date,
    //                     'notes' => 'Accumulated late minutes: ' . $total,
    //                 ]);
    //                 $results[] = ['user_id' => $userId, 'action' => 'created', 'type' => $type];
    //             }
    //         }

    //         return $results;
    //     });
    // }

    public function generateReprimandMessage($type)
    {
        $lateService = new LateService();
        switch ($type) {
            case LateService::LATE_WARNING_LETTER:
                return "Jangan telat lagi!";
            case LateService::LATE_WARNING_LETTER_AND_CALL_TO_HR:
                return "Yang bersangkutan diharap menghadap HRD!";
            case LateService::CUT_LEAVE_AND_WARNING_LETTER:
                return "Yang bersangkutan ga dapet libur dan mendapat surat teguran";
            default:
                return "";
        }
    }

    // determineReprimandType removed: mapping handled by LateService rules
}
