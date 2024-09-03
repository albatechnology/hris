<?php

namespace App\Jobs\Announcement;

use App\Enums\NotificationType;
use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NotifyAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Announcement $announcement, private Collection $users)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notificationType = NotificationType::ANNOUNCEMENT;
        
        foreach($this->users as $user){
            $user->notify(new ($notificationType->getNotificationClass())($notificationType, $user, $this->announcement));
        }
    }
}
