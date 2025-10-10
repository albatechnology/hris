<?php

namespace App\Console\Commands\Timeoff;

use App\Jobs\AnnualLeave\NewEmployee;
use Illuminate\Console\Command;

class ReevaluateNewEmployee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:timeoff-new-employee {end_date?} {start_date?}';

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
        NewEmployee::dispatch();

        $info = "ReevaluateNewEmployee RUNNING";
        $this->info($info);
    }
}
