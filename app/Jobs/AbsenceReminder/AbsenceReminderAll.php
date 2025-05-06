<?php

namespace App\Jobs\AbsenceReminder;

use App\Models\AbsenceReminder;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\AbsenceReminder\AbsenceReminder as AbsenceReminderAbsenceReminder;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AbsenceReminderAll implements ShouldQueue
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
        // dump($now);

        $absenceReminders = AbsenceReminder::query()->has('company')
            ->when(config('app.name') == 'SMART', fn($q) => $q->has('client'))
            ->get();

        $shiftIds = [];
        foreach ($absenceReminders as $absenceReminder) {
            // dump($absenceReminder->toArray());
            $end = $now->copy()->addMinutes($absenceReminder->minutes_before);
            // dump($end);
            $shifts = Shift::select('id')
                ->where('is_dayoff', 0)
                ->where('company_id', $absenceReminder->company_id);

            if ($now->gt($end)) {
                // Jam sekarang lebih besar dari jam end → crossing midnight
                $shifts = $shifts->where(function ($q) use ($now, $end) {
                    $q->whereBetween('clock_in', [$now->format('H:i:s'), '23:59:59'])
                        ->orWhereBetween('clock_in', ['00:00:00', $end->format('H:i:s')]);
                })
                    ->get();
            } else {
                // Normal
                $shifts = $shifts->whereBetween('clock_in', [$now->format('H:i:s'), $end->format('H:i:s')])
                    ->get();
            }

            $shiftIds = [...$shifts->pluck('id')->toArray(), ...$shiftIds];
        }
        // dump($shiftIds);


        $users = User::select('id', 'name', 'fcm_token')
            ->where('company_id', 3)
            ->whereHas('schedules', fn($q) => $q->whereHas('shifts', fn($q) => $q->whereIn('shift_id', $shiftIds)))
            ->get();

        foreach ($users as $user) {
            $user->notify(new AbsenceReminderAbsenceReminder("TEST REMINDER BOS DIFA"));
        }
    }
}
