<?php

namespace App\Console\Commands\Timeoff;

use App\Jobs\Timeoff\ReevaluateTimeOffDisciplineReward as TimeoffReevaluateTimeOffDisciplineReward;
use Illuminate\Console\Command;

class ReevaluateTimeOffDisciplineReward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:timeoff-dicipline-reward {end_date?} {start_date?}';

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
        TimeoffReevaluateTimeOffDisciplineReward::dispatch(startDate: $this->argument('start_date'), endDate: $this->argument('end_date'));

        $info = "TimeoffReevaluateTimeOffDisciplineReward RUNNING";
        if ($this->argument('start_date')) $info .= " " . $this->argument('start_date');
        if ($this->argument('end_date')) $info .= " - " . $this->argument('end_date');
        $this->info($info);
    }
}
