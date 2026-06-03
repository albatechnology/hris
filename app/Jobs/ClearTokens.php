<?php

namespace App\Jobs;

use App\Models\Token;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClearTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Token::query()
            ->where('expired_at', '<', now())
            ->chunkById(1000, function ($tokens) {
                Token::whereIn(
                    'id',
                    $tokens->pluck('id')
                )->delete();
            });
    }
}
