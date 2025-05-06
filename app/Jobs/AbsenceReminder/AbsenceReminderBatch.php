<?php

namespace App\Jobs\AbsenceReminder;

use App\Models\AbsenceReminder;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\AbsenceReminder\AbsenceReminder as AbsenceReminderAbsenceReminder;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AbsenceReminderBatch implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = Carbon::now();

        $absenceReminders = AbsenceReminder::query()->has('company')
            ->where('company_id', 3)
            ->where('minutes_before', '>', 0)
            ->when(config('app.name') == 'SMART', fn($q) => $q->has('client'))
            ->get();

        $shiftIds = [];
        foreach ($absenceReminders as $absenceReminder) {
            $end = $now->copy()->addMinutes($absenceReminder->minutes_before);
            $shifts = Shift::select('id')
                ->where('company_id', 3)
                ->where('is_dayoff', 0)
                ->where('company_id', $absenceReminder->company_id);

            if ($now->gt($end)) {
                // crossing midnight
                $shifts = $shifts->where(function ($q) use ($now, $end) {
                    $q->whereBetween('clock_in', [$now->format('H:i:s'), '23:59:59'])
                        ->orWhereBetween('clock_in', ['00:00:00', $end->format('H:i:s')]);
                })
                    ->get();
            } else {
                $shifts = $shifts
                    ->whereBetween('clock_in', [$now->format('H:i:s'), $end->format('H:i:s')])
                    ->get();
            }

            $shiftIds = [...$shifts->pluck('id')->toArray(), ...$shiftIds];
        }

        $batchSize = 50;
        $totalUsers = User::whereHas('schedules', fn($q) => $q->whereHas('shifts', fn($q) => $q->whereIn('shift_id', $shiftIds)))->count();

        for ($offset = 0; $offset < $totalUsers; $offset += $batchSize) {
            DispatchAbsenceReminder::dispatch($shiftIds, $offset, $batchSize)->delay(now()->addSeconds(($offset / $batchSize) * 15));
        }
    }
}

class DispatchAbsenceReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $shiftIds = [],
        private int $offset,
        private int $limit = 50
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::select('id', 'name', 'fcm_token')
            ->where('company_id', 3)
            ->whereHas('schedules', fn($q) => $q->whereHas('shifts', fn($q) => $q->whereIn('shift_id', $this->shiftIds)))
            ->limit($this->limit)
            ->offset($this->offset)
            ->lazy()
            ->each(function ($user) {
                $schedule = ScheduleService::getTodaySchedule($user, date('Y-m-d'), ['id'], ['id', 'is_dayoff', 'name', 'clock_in']);
                if ($schedule && !$schedule->shift?->is_dayoff && in_array($schedule->shift->id, $this->shiftIds)) {
                    $user->notify(new AbsenceReminderAbsenceReminder("Reminder for " . $schedule->shift->name . " at " . $schedule->shift->clock_in));
                }
            });
    }
}
