<?php

namespace App\Jobs\AbsenceReminder;

use App\Models\User;
use App\Notifications\AbsenceReminder\AbsenceReminder;
use App\Services\ScheduleService;
use Carbon\Carbon;
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
        public int $offset,
        public int $limit = 50,
        public Carbon $start,
        public Carbon $end,
        public ?array $shiftIds = [],
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::select('id', 'name', 'fcm_token')
            ->whereHas('schedules', fn($q) => $q->whereHas('shifts', fn($q) => $q->whereIn('shift_id', $this->shiftIds)))
            ->whereHas(
                'detail',
                fn($q) => $q->whereNull('last_absence_reminder_at')->orWhere('last_absence_reminder_at', '<', $this->start)
            )
            ->limit($this->limit)
            ->offset($this->offset)
            ->lazy()
            ->each(function ($user) {
                $schedule = ScheduleService::getTodaySchedule($user, date('Y-m-d'), ['id'], ['id', 'is_dayoff', 'name', 'clock_in', 'clock_out']);

                if (
                    $schedule
                    && !$schedule->shift?->is_dayoff
                    && in_array($schedule->shift->id, $this->shiftIds)
                ) {
                    $user->detail->update(['last_absence_reminder_at' => $this->end]);
                    $user->notify(new AbsenceReminder("Jangan lupa masuk kerja " . $schedule->shift->name . " pukul " . $schedule->shift->clock_in . " dan lakukan absensi ya!"));
                }
            });
    }
}
