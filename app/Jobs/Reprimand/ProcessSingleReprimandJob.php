<?php

namespace App\Jobs\Reprimand;


use App\Models\Reprimand;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessSingleReprimandJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $reprimandId;

    public $tries = 3;
    public $timeout = 200; // seconds - allow enough for PDF generation

    public function __construct(int $reprimandId)
    {
        $this->reprimandId = $reprimandId;
    }

    public function handle(\App\Services\RunReprimandService $service)
    {
        // Use cache lock to avoid duplicated execution (if multiple dispatchers)
        $lockKey = "process_reprimand_{$this->reprimandId}";


        $lock = Cache::lock($lockKey, 300); // 5 minutes


        if (! $lock->get()) {
            // someone else is processing this item
            return;
        }

        try {
            $reprimand = Reprimand::with([
                'user' => fn($q) => $q->select('id', 'name', 'type', 'fcm_token', 'gender'),
                'runReprimand' => fn($q) => $q->select('id', 'company_id'),
            ])->select('id', 'run_reprimand_id', 'user_id', 'month_type', 'type', 'effective_date', 'context')->where('id', $this->reprimandId)->first();

            if (! $reprimand) return;

            // 1) Generate PDF if applicable
            $service->generatePdf($reprimand);


            // 2) Apply cut leave if rule present
            // $rule = $reprimand->context['rule'];
            // if (!empty($rule['total_cut_leave']) && $rule['total_cut_leave'] > 0) {
            //     $service->cutLeave($reprimand, $rule);
            // }

            // 3) Notify
            $service->notifyUser($reprimand);
        } catch (\Throwable $e) {
            Log::error('ProcessSingleReprimandJob failed', [
                'reprimand_id' => $this->reprimandId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // rethrow to allow Laravel to handle retries
            throw $e;
        } finally {
            // release lock
            optional($lock)->release();
        }
    }
}
