<?php

namespace App\Console\Commands\AbsenceReminder;

use App\Jobs\AbsenceReminder\AbsenceReminderBatch;
use Illuminate\Console\Command;

class AbsenceReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:absence-reminder {company_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manual send absence reminder, for testing purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        AbsenceReminderBatch::dispatch($this->argument('company_id'));

        $info = "AbsenceReminderBatch RUNNING";
        if ($this->argument('company_id')) $info .= " for company " . $this->argument('company_id');
        $this->info($info);
    }
}
