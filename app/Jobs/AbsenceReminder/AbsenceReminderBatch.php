<?php

namespace App\Jobs\AbsenceReminder;

use App\Models\AbsenceReminder;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AbsenceReminderBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = Carbon::now();

        $absenceReminders = AbsenceReminder::query()->has('company')
            ->where('is_active', 1)
            ->when(config('app.name') == 'Syntegra', fn($q) => $q->has('client'))
            ->get();

        $shiftIds = [];
        foreach ($absenceReminders as $absenceReminder) {
            $end = $now->copy()->addMinutes($absenceReminder->minutes_before);
            $shifts = Shift::select('id')
                ->where('is_dayoff', 0)
                ->where('company_id', $absenceReminder->company_id);

            if ($end->gt($now)) {
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
