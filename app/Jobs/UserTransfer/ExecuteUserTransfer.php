<?php

namespace App\Jobs\UserTransfer;

use App\Http\Services\UserTransfer\UserTransferService;
use App\Models\UserTransfer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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
    public function handle(UserTransferService $service): void
    {
        $userTransfers = UserTransfer::whereNull('executed_at')->whereDate('effective_date', date('Y-m-d'))->get();
        foreach ($userTransfers as $userTransfer) {
            try {
                $service->execute($userTransfer);
            } catch (\Throwable $e) {
                throw $e;
                Log::error("Gagal execute UserTransfer ID: {$userTransfer->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}
