<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use App\Enums\ReprimandMonthType;
use App\Enums\TimeoffRequestType;
use App\Http\Requests\Api\RunReprimand\StoreRequest;
use App\Models\RunReprimand;
use App\Models\User;
use App\Enums\ReprimandType;
use App\Enums\RunReprimandStatus;
use App\Jobs\Reprimand\ProcessSingleReprimandJob;
use App\Models\Reprimand;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
     * @param object{runReprimand: RunReprimand, start_drequestate: StoreRequest} $request
     */
    private function createReprimand(RunReprimand $runReprimand, StoreRequest $request): void
    {
        $userIds = $request->user_ids ? explode(',', $request->user_ids) : null;
        $users = User::tenanted()->select('id', 'name', 'join_date')
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->where('company_id', $request->company_id)
            ->whereDoesntHave('reprimands', fn($q) => $q->whereYear('effective_date', date('Y', strtotime($request->start_date)))->whereMonth('effective_date', date('m', strtotime($request->start_date))))
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

        $dateRange = CarbonPeriod::create($request->start_date, $request->end_date);

        foreach ($users as $user) {
            $userAttendances = $user->attendances;

            $totalLateMinutes = 0;
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

                $totalLatePerDay = ($minutesIn ?? 0) + ($minutesOut ?? 0);

                if ($totalLatePerDay == 0) {
                    continue;
                }

                $totalLateMinutes += $totalLatePerDay;

                $perDay[] = [
                    'attendance_id' => $attendance->id,
                    'attendance_date' => $attendance->date,
                    'late_in_minutes' => $minutesIn,
                    'late_out_minutes' => $minutesOut,
                    'total' => $totalLatePerDay,
                ];
            }
            // dd($totalLateMinutes);
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
                'context' => [
                    'attendances' => $perDay,
                ]
            ]);

            $this->setReprimandType($reprimand);

            $reprimand->save();
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


        $monthTypeRule = $reprimand->month_type->getRule($reprimand->type);
        $reprimand->context = array_merge(
            $reprimand->context ?? [],
            ['rule' => $monthTypeRule]
        );

        return $reprimand;
    }

    public function update(int $id, array $data)
    {
        $runReprimand = RunReprimand::findTenanted($id);

        if ($runReprimand->status->is(RunReprimandStatus::RELEASE)) {
            throw new BadRequestHttpException("Run Reprimand status was {$runReprimand->status->value}, and can not change");
        }

        DB::transaction(function () use ($runReprimand, $data) {
            $runReprimand->update($data);


            if ($runReprimand->status->is(RunReprimandStatus::RELEASE)) {
                // only pluck IDs to save memory
                $reprimandIds = $runReprimand->reprimands()->select('id')->pluck('id');

                $batchSize = 100; // number of jobs in one batch dispatch - tune as needed

                $jobs = [];
                foreach ($reprimandIds as $rid) {
                    $jobs[] = new ProcessSingleReprimandJob($rid);


                    // Dispatch batches in groups to avoid creating extremely large batch objects
                    if (count($jobs) >= $batchSize) {
                        Bus::batch($jobs)
                            ->name("Process Reprimands: run_reprimand_{$runReprimand->id}")
                            ->allowFailures()
                            ->dispatch();


                        $jobs = [];
                    }
                }

                // dispatch remaining
                if (!empty($jobs)) {
                    Bus::batch($jobs)
                        ->name("Process Reprimands: run_reprimand_{$runReprimand->id}_part2")
                        ->allowFailures()
                        ->dispatch();
                }
            }
        });

        // DB::transaction(function () use ($runReprimand, $data) {
        //     $runReprimand->update($data);

        //     if ($runReprimand->status->is(RunReprimandStatus::RELEASE)) {
        //         $runReprimand->reprimands()
        //             ->with([
        //                 'user' => fn($q) => $q->select('id', 'name', 'type', 'fcm_token', 'gender'),
        //                 'runReprimand' => fn($q) => $q->select('id', 'company_id'),
        //             ])
        //             ->select('id', 'run_reprimand_id', 'user_id', 'month_type', 'type', 'effective_date', 'context')
        //             ->chunk(100, function ($reprimands) {
        //                 foreach ($reprimands as $reprimand) {
        //                     $this->generatePdf($reprimand);

        //                     $monthTypeRule = $reprimand->month_type->getRule($reprimand->type);
        //                     if (isset($monthTypeRule['total_cut_leave']) && $monthTypeRule['total_cut_leave'] > 0) {
        //                         $this->cutLeave($reprimand, $monthTypeRule);
        //                     }

        //                     $notificationType = NotificationType::REPRIMAND;
        //                     $reprimand->user->notify(new ($notificationType->getNotificationClass())($notificationType, $reprimand));
        //                 }
        //             });
        //     }
        // });
    }

    // make public because this function called by ProcessSingleReprimandJob
    public function generatePdf(Reprimand $reprimand)
    {
        $monthTypeRule = $reprimand->context['rule'];

        $user = $reprimand->user->load(['positions' => fn($q) => $q->with([
            'department' => fn($q) => $q->select('id', 'name'),
            'position' => fn($q) => $q->select('id', 'name'),
        ])]);

        $position = null;
        $department = null;
        if ($user->positions->count()) {
            $position = $user->positions[0]->position?->name ?? null;
            $department = $user->positions[0]->department?->name ?? null;
        }

        $data = [
            'reprimand' => $reprimand,
            'number' => rand(100, 999),
            'user_name' => $user->name,
            'user_title' => $user->gender->getTitle(),
            'position' => $position,
            'department' => $department,
            'letter_title' => $monthTypeRule['letter_title'],
            'letter_subject' => $monthTypeRule['letter_subject'],
            'letter_number' => $monthTypeRule['letter_number'],
        ];

        $month = date('m', strtotime($reprimand->effective_date));
        $year = date('Y', strtotime($reprimand->effective_date));

        // pelanggaran bulan pertama tidak ribet datanya, makanya pake ini aja
        if ($reprimand->type->isSendWarningLetter()) {
            $dates = collect($reprimand->context['attendances'])->map(fn($attendance) => date("F jS, Y", strtotime($attendance['attendance_date'])));

            $data['dates'] = $dates;
            $pdfViewPath = 'api.exports.pdf.reprimand.warning-letter';
        } elseif ($reprimand->type->isSendSPLetter()) {
            $allPreviousMonths = $reprimand->month_type->getAllPreviousMonths();

            // get nama bulan reprimand sebelumnya, masih dalam bentuk collections
            $allReprimandMonths = Reprimand::selectRaw('MONTHNAME(effective_date) as month')
                ->where(function ($query) use ($allPreviousMonths, $month, $year) {
                    // We modify the $month and $year variables within the loop scope.
                    $currentMonth = (int)$month;
                    $currentYear = (int)$year;

                    foreach ($allPreviousMonths as $mt) {
                        // Use orWhere inside the *main* closure
                        $query->orWhere(function ($q) use ($currentYear, $currentMonth, $mt) {
                            $q->whereYear('effective_date', $currentYear)
                                ->whereMonth('effective_date', $currentMonth)
                                ->where('month_type', $mt);
                        });

                        // Decrement month and handle year change *after* adding the condition
                        $currentMonth--;
                        if ($currentMonth < 1) {
                            $currentMonth = 12;
                            $currentYear--;
                        }
                    }
                })->orderBy('effective_date')->pluck('month');

            // format nama bulan menjadi
            // SP1: January, February, and March
            // SP2: January, February, March, and April
            // SP2: January through Mei
            $formattedMonths = '';
            if ($reprimand->month_type->is(ReprimandMonthType::MONTH_3_VIOLATION_3)) {
                if ($allReprimandMonths->count() === 1) {
                    $formattedMonths = "in {$allReprimandMonths->first()}";
                } else {
                    $first = $allReprimandMonths->first();
                    $last = $allReprimandMonths->last();
                    $formattedMonths = "from {$first} through {$last}";
                }
            } else {
                $months = $allReprimandMonths->unique()->values()->toArray();

                if (count($months) > 1) {
                    if ($reprimand->month_type->is(ReprimandMonthType::MONTH_3_VIOLATION_3)) {
                        $formattedMonths = $months[0] . ' through ' . end($months);
                    } else {
                        $last = array_pop($months);
                        $formattedMonths = implode(', ', $months) . ', and ' . $last;
                    }
                } else {
                    $formattedMonths = $months[0] ?? '';
                }
            }
            $data['formatted_months'] = $formattedMonths;
            $pdfViewPath = 'api.exports.pdf.reprimand.sp-letter';
        } else {
            // reprimand yang tidak perlu generate pdf langsung return aja
            return;
        }

        // 1. Tentukan nama file yang unik
        $fileName = sprintf(
            "reprimand-%s-%s-%s-%s.pdf",
            $month,
            $year,
            $user->name,
            uniqid()
        );

        // 2. Tentukan jalur penyimpanan temporary di disk lokal
        // Spatie akan mengambil dari sini
        $tempPath = storage_path('app/temp/' . $fileName);

        // Pastikan direktori 'temp' ada
        if (!File::exists(storage_path('app/temp'))) {
            File::makeDirectory(storage_path('app/temp'));
        }

        // 3. Generate PDF dan simpan secara LOKAL (sementara)
        Pdf::loadView($pdfViewPath, $data)
            ->setPaper('a4')
            ->save($tempPath);

        // 4. Gunakan Spatie addMedia() untuk memindahkan file ke S3
        if (File::exists($tempPath)) {
            $reprimand->addMedia($tempPath)
                ->usingFileName($fileName) // Nama file di S3
                ->toMediaCollection(); // Nama koleksi media Anda


            // 5. Hapus file sementara dari disk lokal setelah berhasil diupload ke S3
            File::delete($tempPath);
        }
    }

    // make public because this function called by ProcessSingleReprimandJob
    public function cutLeave(Reprimand $reprimand, array $rule): void
    {
        // timeoff policy AL sunshine 1, sun malay 14
        $timeoffPolicyId = $reprimand->runReprimand->company_id == 1 ? 1 : 14;
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');

        $timeoffQuotas = $reprimand->user->timeoffQuotas()
            ->where('timeoff_policy_id', $timeoffPolicyId)
            ->whereActiveNew($startDate, $endDate)
            ->get();

        // dump($timeoffQuotas->toArray());

        $remainingTotalCutOff = $rule['total_cut_leave'];
        // dump($remainingTotalCutOff);
        if ($timeoffQuotas->count()) {
            foreach ($timeoffQuotas as $timeoffQuota) {
                // dump($timeoffQuota->toArray());

                $oldBalance = $timeoffQuota->balance;
                // dump($oldBalance);

                // Check if the quota is exceeded
                if ($remainingTotalCutOff > $oldBalance) {
                    $timeoffQuota->used_quota += $oldBalance;
                    $remainingTotalCutOff -= $oldBalance;
                } else {
                    // If quota is not exceeded, update the used quota
                    $timeoffQuota->used_quota += $remainingTotalCutOff;
                    $remainingTotalCutOff = 0;
                }
                // dump($oldBalance);
                // dump($remainingTotalCutOff);
                // dump($timeoffQuota->toArray());

                $timeoffQuota->save();

                $timeoffQuota->timeoffQuotaHistories()->create([
                    'user_id' => $reprimand->user_id,
                    'is_increment' => false,
                    'old_balance' => $oldBalance,
                    'new_balance' => $timeoffQuota->quota - $timeoffQuota->used_quota,
                    'description' => "CUT LEAVE FROM REPRIMAND " . date('F', strtotime($reprimand->effective_date)) . ' ' . date('Y', strtotime($reprimand->effective_date))
                ]);

                if ($remainingTotalCutOff == 0) {
                    return;
                }

                // dump($oldBalance);
            }
        }

        // hapus absensi di hari melanggar
        $remainingTotalCutOff = ceil($remainingTotalCutOff);
        // dump($reprimand->context);

        foreach ($reprimand?->context['attendances'] ?? [] as $c) {
            if ($remainingTotalCutOff < 1) return;
            $date = $c['attendance_date'] ?? null;
            if ($date) {
                // mungkin kedepan harus tambahin query whereNull('reprimand_id')
                $attendance = $reprimand->user->attendance()->where('date', $date)->first(['id', 'reprimand_id']);

                if ($attendance) {
                    $attendance->reprimand_id = $reprimand->id;
                    $attendance->save();

                    $remainingTotalCutOff--;
                }
            }
        }

        // kalo remainingTotalCutOff masih sisa biarin aja
    }

    public function notifyUser(Reprimand $reprimand): void
    {
        $notificationType = \App\Enums\NotificationType::REPRIMAND;
        $reprimand->user->notify(new ($notificationType->getNotificationClass())($notificationType, $reprimand));
    }

    // public function allReprimand(RunReprimand $runReprimand): array
    // {
    //     // preview-only: return calculation results without persisting
    //     $results = [];
    //     $lateService = new LateService();

    //     $users = User::select('id', 'name', 'join_date')
    //         ->where('company_id', $runReprimand->company_id)
    //         ->get();
    //     $reprimandCountByUser = Reprimand::select('user_id', DB::raw('COUNT(*) as total'))
    //         ->groupBy('user_id')
    //         ->pluck('total', 'user_id');

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

    //     $currentMonth = Carbon::parse($runReprimand->start_date)->startOfMonth();

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

    //         $total = (int)$userTotal;

    //         // Tentukan stage (bulan ke-X) berdasarkan streak reprimand bulan-bulan sebelumnya
    //         $prevStreak = $this->getPrevConsecutiveReprimandStreak($user->id, $currentMonth);
    //         $stage = min($prevStreak + 1, 5);

    //         // Hitung violation index di bulan berjalan (biasanya 1)
    //         $violationIndex = $this->getCurrentMonthViolationIndex(
    //             $user->id,
    //             Carbon::parse($runReprimand->start_date),
    //             Carbon::parse($runReprimand->end_date)
    //         ) + 1;

    //         // Evaluasi pinalti untuk total menit ini
    //         $eval = $lateService->evaluatePenalty($total, $stage, $violationIndex);

    //         $row = [
    //             'user_id' => $user->id,
    //             'name' => $user->name,
    //             'total_late_minutes' => $userTotal,
    //             'count' => (int) ($reprimandCountByUser[$user->id] ?? 0),
    //             'context' => $perDay,
    //             'is_over_threshold' => $total > LateService::MINUTES_TRESHOLD,
    //         ];

    //         // Tambahkan indikator apakah user pernah telat (punya reprimand) di bulan sebelumnya
    //         $hadPrevMonthReprimand = ((int) ($reprimandPrevMonthByUser[$user->id] ?? 0)) > 0;
    //         $row['is_late_prev_month_ago'] = $hadPrevMonthReprimand; // true/false
    //         // Jika butuh format string: 'ya' / 'tidak', gunakan baris di bawah ini
    //         // $row['is_late_prev_month_ago_label'] = $hadPrevMonthReprimand ? 'ya' : 'tidak';

    //         if ($message !== '') {
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
    //  * Persist all reprimands using evaluated rules per-user. Idempotent per-run.
    //  */
    // public function applyAllReprimand(RunReprimand $runReprimand, string $unused = ''): array
    // {
    //     $lateService = new LateService();

    //     return DB::transaction(function () use ($runReprimand, $lateService) {
    //         $results = [];

    //         // gunakan preview yang sudah menghitung stage/ruleset/type
    //         $preview = $this->allReprimand($runReprimand);

    //         // tandai run sudah dirilis
    //         $runReprimand->update(['status' => 'release']);

    //         foreach ($preview as $row) {
    //             $userId = $row['user_id'];
    //             $total = (int)$row['total_late_minutes'];
    //             $type = $row['reprimand_type'] ?? '-'; // fallback aman
    //             $notes = trim('Accumulated late minutes: ' . $total . ($row['total_cut_leave'] ? " | cut leave: {$row['total_cut_leave']}" : ''));

    //             $existing = $runReprimand->reprimands()->where('user_id', $userId)->first();
    //             if ($existing) {
    //                 $existing->update([
    //                     'type' => $type,
    //                     'notes' => $notes,
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
    //                     'notes' => $notes,
    //                 ]);
    //                 $results[] = ['user_id' => $userId, 'action' => 'created', 'type' => $type];
    //             }
    //         }

    //         return $results;
    //     });
    // }

    // /**
    //  * Hitung streak reprimand berturut-turut pada bulan-bulan sebelum $month (tidak termasuk bulan $month).
    //  * Streak berhenti pada bulan pertama tanpa reprimand.
    //  */
    // private function getPrevConsecutiveReprimandStreak(int $userId, Carbon $month): int
    // {
    //     $streak = 0;
    //     $cursor = $month->copy()->subMonthNoOverflow()->startOfMonth();

    //     // Batasi maksimal 12 iterasi untuk keamanan performa
    //     for ($i = 0; $i < 12; $i++) {
    //         $start = $cursor->copy()->startOfMonth()->toDateString();
    //         $end = $cursor->copy()->endOfMonth()->toDateString();

    //         $had = Reprimand::query()
    //             ->where('user_id', $userId)
    //             ->whereBetween('effective_date', [$start, $end])
    //             ->exists();

    //         if ($had) {
    //             $streak++;
    //             $cursor->subMonthNoOverflow();
    //         } else {
    //             break;
    //         }
    //     }

    //     return $streak;
    // }

    // /**
    //  * Banyaknya reprimand user pada bulan berjalan (untuk violation index).
    //  */
    // private function getCurrentMonthViolationIndex(int $userId, Carbon $start, Carbon $end): int
    // {
    //     return (int) Reprimand::query()
    //         ->where('user_id', $userId)
    //         ->whereBetween('effective_date', [
    //             $start->copy()->startOfDay()->toDateTimeString(),
    //             $end->copy()->endOfDay()->toDateTimeString(),
    //         ])
    //         ->count();
    // }

    // public function generateReprimandMessage($type)
    // {
    //     $lateService = new LateService();
    //     switch ($type) {
    //         case LateService::LATE_WARNING_LETTER:
    //             return "Jangan telat lagi!";
    //         case LateService::LATE_WARNING_LETTER_AND_CALL_TO_HR:
    //             return "Yang bersangkutan diharap menghadap HRD!";
    //         case LateService::CUT_LEAVE_AND_WARNING_LETTER:
    //             return "Yang bersangkutan ga dapet libur dan mendapat surat teguran";
    //         default:
    //             return "";
    //     }
    // }
}
