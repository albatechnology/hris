<?php

namespace App\Jobs\UserTransfer;

use App\Models\UserTransfer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExecuteUserTransfer implements ShouldQueue
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
        $userTransfers = UserTransfer::whereNull('executed_at')->whereDate('effective_date', date('Y-m-d'))->get();

        foreach ($userTransfers as $userTransfer) {

        }
    }
}
