<?php

namespace App\Jobs;

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateAttendancesOneMonthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 3600; // 1 hour

    protected $userIds;
    protected $startDate;
    protected $endDate;

    public function __construct(array $userIds, string $startDate, string $endDate)
    {
        $this->userIds = $userIds;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function handle()
    {
        Log::info("Starting GenerateAttendancesOneMonthJob for users " . implode(',', $this->userIds) . " from {$this->startDate} to {$this->endDate}");

        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        // Get users with minimal columns
        $users = User::whereIn('id', $this->userIds)
            ->select('id', 'group_id', 'company_id', 'type', 'resign_date')
            ->whereNull('deleted_at')
            ->get();

        $createdCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            // Loop through each date in the range
            for ($currentDate = $startDate->copy(); $currentDate->lte($endDate); $currentDate->addDay()) {
                $dateString = $currentDate->toDateString();

                foreach ($users as $user) {
                    // Skip if user is resigned
                    if ($user->resign_date && Carbon::parse($user->resign_date)->lte($dateString)) {
                        $skippedCount++;
                        continue;
                    }

                    // Find attendance for this user and date, including clock-in / clock-out detail relations
                    $attendance = Attendance::select('id', 'user_id', 'date')
                        ->where('user_id', $user->id)
                        ->where('date', $dateString)
                        ->with([
                            'clockIn' => fn($q) => $q->select('id', 'attendance_id', 'type'),
                            'clockOut' => fn($q) => $q->select('id', 'attendance_id', 'type'),
                        ])
                        ->first();

                    // Get schedule for the user on this date
                    $schedule = ScheduleService::getTodaySchedule($user, $dateString);
                    if (!$schedule || !$schedule->shift || $schedule->shift->id == 1) {
                        $skippedCount++;
                        continue;
                    }

                    $shift = $schedule->shift;

                    if (!$attendance) {
                        $attendance = Attendance::create([
                            'user_id' => $user->id,
                            'date' => $dateString,
                            'schedule_id' => $schedule->id,
                            'shift_id' => $shift->id,
                        ]);
                        $createdCount++;
                    }

                    if ($attendance->clockIn) {
                        $attendance->clockIn->update(['type' => AttendanceType::AUTOMATIC]);
                    } else {
                        $attendance->details()->create([
                            'is_clock_in' => true,
                            'time' => $dateString . ' ' . $shift->clock_in,
                            'type' => AttendanceType::AUTOMATIC,
                        ]);
                        $createdCount++;
                    }

                    if ($attendance->clockOut) {
                        $attendance->clockOut->update(['type' => AttendanceType::AUTOMATIC]);
                    } else {
                        $attendance->details()->create([
                            'is_clock_in' => false,
                            'time' => $dateString . ' ' . $shift->clock_out,
                            'type' => AttendanceType::AUTOMATIC,
                        ]);
                        $createdCount++;
                    }
                }
            }

            DB::commit();

            Log::info("GenerateAttendancesOneMonthJob completed. Created: {$createdCount}, Skipped: {$skippedCount}");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("GenerateAttendancesOneMonthJob failed: " . $e->getMessage());
            throw $e;
        }
    }
}