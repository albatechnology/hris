<?php

namespace App\Console;

use App\Jobs\AbsenceReminder\AbsenceReminderBatch;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        $schedule->job(new \App\Jobs\AnnualLeave\NewEmployee)->monthlyOn(1, '12:10');
        // $schedule->job(new \App\Jobs\AnnualLeave\NewEmployeeCharyuning)->dailyAt('18:06');
        $schedule->job(new \App\Jobs\UserTransfer\ExecuteUserTransfer)->dailyAt('02:00');
        $schedule->job(new AbsenceReminderBatch())->everyFiveMinutes()->when(fn () => config('app.name') === 'SYNTEGRA');

        // cron untuk company yang punya timeoff regulation monthly
        // cron untuk company yang punya timeoff regulation user_period
        // cron untuk company yang punya timeoff regulation period

        // cron untuk cek user dapet dayoff
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
