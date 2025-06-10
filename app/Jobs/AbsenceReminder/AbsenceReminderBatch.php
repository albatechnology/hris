<?php

namespace App\Jobs\AbsenceReminder;

use App\Models\AbsenceReminder;
use App\Models\Shift;
use App\Models\User;
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
    public function __construct(private ?int $companyId = null)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $start = now();
        $batchSize = 50;

        $absenceReminders = AbsenceReminder::query()->has('company')
            ->where('is_active', 1)
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when(config('app.name') == 'Syntegra', fn($q) => $q->has('branch'))
            ->get();

        foreach ($absenceReminders as $absenceReminder) {
            $end = $start->copy()->addMinutes($absenceReminder->minutes_before);
            $shifts = Shift::select('id')
                ->where('is_dayoff', 0)
                ->where('company_id', $absenceReminder->company_id);

            if ($end->gt($start)) {
                // crossing midnight
                $shifts = $shifts->where(function ($q) use ($start, $end) {
                    $q->whereBetween('clock_in', [$start->format('H:i:s'), '23:59:59'])
                        ->orWhereBetween('clock_in', ['00:00:00', $end->format('H:i:s')]);
                })->get();
            } else {
                $shifts = $shifts
                    ->whereBetween('clock_in', [$start->format('H:i:s'), $end->format('H:i:s')])
                    ->get();
            }

            $shiftIds = $shifts->pluck('id')->toArray();

            $totalUsers = User::whereHas('schedules', fn($q) => $q->whereHas('shifts', fn($q) => $q->whereIn('shift_id', $shiftIds)))
                ->whereHas(
                    'detail',
                    fn($q) => $q->whereNull('last_absence_reminder_at')->orWhere('last_absence_reminder_at', '<', $start)
                )
                ->count();

            for ($offset = 0; $offset < $totalUsers; $offset += $batchSize) {
                DispatchAbsenceReminder::dispatch($offset, $batchSize, $start, $end, $shiftIds)->delay(now()->addSeconds(($offset / $batchSize) * 15));
            }
        }
    }
}
