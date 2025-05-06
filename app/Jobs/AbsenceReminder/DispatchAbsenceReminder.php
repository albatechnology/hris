<?php

namespace App\Jobs\AbsenceReminder;

use App\Models\User;
use App\Notifications\AbsenceReminder\AbsenceReminder;
use App\Services\ScheduleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchAbsenceReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $shiftIds = [],
        public int $offset,
        public int $limit = 50
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
                    $user->notify(new AbsenceReminder("Reminder for " . $schedule->shift->name . " at " . $schedule->shift->clock_in));
                }
            });
    }
}
