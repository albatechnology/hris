<?php

namespace App\Jobs\Announcement;

use App\Enums\NotificationType;
use App\Models\Announcement;
use App\Notifications\Announcement\AnnouncementBulkNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class BulkNotifyAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Announcement $announcement, private Collection $users) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notificationType = NotificationType::ANNOUNCEMENT;

        Notification::send($this->announcement->user, new AnnouncementBulkNotification($notificationType, $this->announcement, $this->users->pluck('fcm_token')->toArray()));
    }
}
