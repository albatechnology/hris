<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\ReprimandMonthType;
use App\Enums\TimeoffRequestType;
use App\Http\Requests\Api\RunReprimand\StoreRequest;
use App\Models\Attendance;
use App\Models\RunReprimand;
use App\Models\User;
use App\Enums\ReprimandType;
use App\Enums\RunReprimandStatus;
use App\Models\Reprimand;
use App\Notifications\Reprimand\ReprimandNotification;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RunReprimandService
{
    public function store(StoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $runReprimand = RunReprimand::create($request->validated());

            $this->createReprimand($runReprimand, $request);
        });
    }

    /**
     * Handle the creation of reprimands for users.
     *
     * @param object{company_id: int, start_date: string, end_date: string, user_ids?: string} $request
     */
    public function createReprimand(RunReprimand $runReprimand, StoreRequest $request): void
    {
        $userIds = $request->user_ids ? explode(',', $request->user_ids) : null;
        $users = User::tenanted()->select('id', 'name', 'join_date')
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->where('company_id', $request->company_id)
            ->with(
                'attendances',
                fn($q) => $q->select('id', 'user_id', 'shift_id', 'date', 'timeoff_id')
                    ->whereDateBetween($request->start_date, $request->end_date)
                    ->where(function ($q) {
                        $q->whereNull('timeoff_id')
                            ->orWhereHas('timeoff', fn($q) => $q->where('request_type', '!=', TimeoffRequestType::FULL_DAY));
                    })
                    ->withWhereHas('clockIn', fn($q) => $q->approved()->select('attendance_id', 'time', 'is_clock_in'))
                    ->withWhereHas('clockOut', fn($q) => $q->approved()->select('attendance_id', 'time', 'is_clock_in'))
                    ->withWhereHas('shift', fn($q) => $q->withTrashed()->where('is_dayoff', 0)->selectMinimalist(['is_enable_grace_period', 'time_dispensation', 'clock_in', 'clock_out']))
            )
            ->get();
        // dd($users);

        $dateRange = CarbonPeriod::create($request->start_date, $request->end_date);
        $results = [];

        foreach ($users as $user) {
            $userAttendances = $user->attendances;

            $totalLateMinutes = 0;
            $perDay = [];

            foreach ($dateRange as $date) {
                $attendance = $userAttendances->firstWhere('date', $date->format('Y-m-d'));
                if (!$attendance || !$attendance->shift) continue;

                // dump($attendance->toArray());
                $remaining = 0;
                $minutesIn = 0;
                $minutesOut = 0;

                if ($attendance->clockIn) {
                    list($minutesIn, $diffInTime, $remaining) = AttendanceService::getTotalLateTime($attendance->clockIn, $attendance->shift, $remaining);
                    // dump($minutesIn);
                    // dump($diffInTime);
                    // dump($remaining);
                }

                if ($attendance->clockOut) {
                    list($minutesOut, $diffInTime2, $remaining) = AttendanceService::getTotalLateTime($attendance->clockOut, $attendance->shift, $remaining);
                    // dump($minutesOut);
                    // dump($diffInTime2);
                    // dump($remaining);
                }

                // dump('HEHEHOHO');

                $totalLatePerDay = ($minutesIn ?? 0) + ($minutesOut ?? 0);
                // dump($totalLatePerDay);
                $totalLateMinutes += $totalLatePerDay;
                // dump($totalLateMinutes);

                $perDay[$date->format('Y-m-d')] = [
                    'attendance_id' => $attendance->id,
                    'late_in_minutes' => $minutesIn,
                    'late_out_minutes' => $minutesOut,
                    'total' => $totalLatePerDay,
                ];
                // dd($perDay);
            }

            // abaikan jika tidak ada keterlambatan
            if ($totalLateMinutes == 0) {
                continue;
            }

            $reprimand = new Reprimand([
                'run_reprimand_id' => $runReprimand->id,
                'user_id' => $user->id,
                'month_type' => ReprimandMonthType::MONTH_1_VIOLATION_1,
                'type' => ReprimandType::TOLERANCE,
                'total_late_minutes' => $totalLateMinutes,
                'effective_date' => $runReprimand->start_date,
                'end_date' => $runReprimand->end_date,
                'details' => $perDay,
            ]);

            // $reprimand->save();

            // dump($reprimand->toArray());
            $this->setReprimandType($reprimand);
            // dump("MANTUL");
            // dd($reprimand->toArray());

            $reprimand->save();

            // $results[] = [
            //     'user_id' => $user->id,
            //     'name' => $user->name,
            //     'total_late_minutes' => $totalLateMinutes,
            //     'details' => $perDay,
            // ];
        }
    }

    private function setReprimandType(Reprimand $reprimand): Reprimand
    {
        $month = date('m', strtotime($reprimand->effective_date . '-1 month'));
        if ($month == 12) {
            $year = date('Y', strtotime($reprimand->effective_date . '-1 year'));
        } else {
            $year = date('Y', strtotime($reprimand->effective_date));
        }

        $prevReprimand = Reprimand::select('id', 'user_id', 'month_type', 'type')
            ->where('user_id', $reprimand->user_id)
            ->whereHas(
                'runReprimand',
                fn($q) => $q->where('status', RunReprimandStatus::RELEASE)
                    ->whereMonth('start_date', $month)
                    ->whereYear('start_date', $year)
            )
            ->first();

        $reprimand->month_type = $prevReprimand?->month_type->next() ?? $reprimand->month_type;
        $reprimand->type = $reprimand->month_type->getReprimandType($reprimand->total_late_minutes);

        return $reprimand;
    }

    public function update(int $id, array $data)
    {
        $runReprimand = RunReprimand::findTenanted($id);
        dump($data);

        DB::transaction(function () use ($runReprimand, $data) {
            $runReprimand->update($data);

            if ($runReprimand->status->is(RunReprimandStatus::RELEASE)) {

                $this->release($runReprimand);
            }
        });
    }

    private function release(RunReprimand $runReprimand): void
    {
        dump($runReprimand->toArray());

        $runReprimand->reprimands()->select('id', 'run_reprimand_id', 'user_id', 'month_type', 'type')->chunk(100, function ($reprimands) {
            foreach ($reprimands as $reprimand) {
                dump($reprimand);
                dump($reprimand->month_type);

                $rule = $reprimand->month_type->getRule($reprimand->type);
                dump($rule);
                if (isset($rule['total_cut_leave']) && $rule['total_cut_leave'] > 0) {
                    $this->cutLeave($reprimand, $rule);
                }

                if (isset($rule['mail_class']) && !is_null($rule['mail_class'])) {
                    $notificationType = NotificationType::REPRIMAND;
                    $reprimand->user->notify(new ($notificationType->getNotificationClass())($notificationType, $reprimand, $rule['mail_class']));
                }
            }
        });

        // dd('opleke');
    }

    private function cutLeave(Reprimand $reprimand, array $rule): void
    {
        $timeoffPolicyId = 1;
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');

        $timeoffQuotas = $reprimand->user->timeoffQuotas()
            ->with('timeoffQuotaHistories')
            ->where('timeoff_policy_id', $timeoffPolicyId)
            ->whereActiveNew($startDate, $endDate)
            ->orderByDesc('effective_start_date')
            ->get();

        $remainingTotalCutOff = $rule['total_cut_leave'];
        if ($timeoffQuotas->count()) {
            foreach ($timeoffQuotas as $timeoffQuota) {
                dump($timeoffQuota);

                dump($remainingTotalCutOff);

                $oldBalance = $timeoffQuota->balance;
                // Check if the quota is exceeded
                if ($remainingTotalCutOff > $oldBalance) {
                    $timeoffQuota->used_quota += $oldBalance;
                    $remainingTotalCutOff -= $oldBalance;
                } else {
                    // If quota is not exceeded, update the used quota
                    $timeoffQuota->used_quota += $remainingTotalCutOff;
                    $remainingTotalCutOff = 0;
                }

                $timeoffQuota->save();

                $timeoffQuota->timeoffQuotaHistories()->create([
                    'user_id' => $reprimand->user_id,
                    'is_increment' => false,
                    'old_balance' => $oldBalance,
                    'new_balance' => $timeoffQuota->quota - $timeoffQuota->used_quota,
                    'description' => "CUT LEAVE FROM REPRIMAND"
                ]);

                if ($remainingTotalCutOff == 0) {
                    return;
                }
            }
        }

        // hapus absensi di hari melanggar

        dd($timeoffQuotas->toArray());
    }

    public function allReprimand(RunReprimand $runReprimand): array
    {
        // preview-only: return calculation results without persisting
        $results = [];
        $lateService = new LateService();

        $users = User::select('id', 'name', 'join_date')
            ->where('company_id', $runReprimand->company_id)
            ->get();
        $reprimandCountByUser = Reprimand::select('user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        // Hitung apakah user punya reprimand pada bulan sebelumnya (berdasarkan start_date run ini)
        $prevMonthStart = Carbon::parse($runReprimand->start_date)->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $prevMonthEnd = Carbon::parse($runReprimand->start_date)->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
        $reprimandPrevMonthByUser = Reprimand::select('user_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('effective_date', [$prevMonthStart, $prevMonthEnd])
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

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
                    'late_in_minutes' => $minutesIn,
                    'late_out_minutes' => $minutesOut,
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
                'total_late_minutes' => $userTotal,
                'count' => (int) ($reprimandCountByUser[$user->id] ?? 0),
                'details' => $perDay,
                'is_over_threshold' => $total > LateService::MINUTES_TRESHOLD,
            ];

            // Tambahkan indikator apakah user pernah telat (punya reprimand) di bulan sebelumnya
            $hadPrevMonthReprimand = ((int) ($reprimandPrevMonthByUser[$user->id] ?? 0)) > 0;
            $row['is_late_prev_month_ago'] = $hadPrevMonthReprimand; // true/false
            // Jika butuh format string: 'ya' / 'tidak', gunakan baris di bawah ini
            // $row['is_late_prev_month_ago_label'] = $hadPrevMonthReprimand ? 'ya' : 'tidak';

            if ($message !== '') {
                $row['reprimand_type'] = $type;
                $row['preview_message'] = $message;
            }

            if ($userTotal > 10) {
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
                $total = (int)$row['total_late_minutes'];
                $type = $row['reprimand_type'] ?? '-'; // fallback aman
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
    //                 'late_in_minutes' => $minutesIn,
    //                 'late_out_minutes' => $minutesOut,
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
    //             'total_late_minutes' => $userTotal,
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
    //             $total = (int) $row['total_late_minutes'];

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
