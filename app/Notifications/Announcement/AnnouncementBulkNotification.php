<?php

namespace App\Notifications\Announcement;

use App\Broadcasting\FcmBulkChannel;
use App\Enums\NotificationType;
use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AnnouncementBulkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private NotificationType $notificationType, private Announcement $announcement, private array $tokens)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): string
    {
        return FcmBulkChannel::class;
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): AnnouncementMailable
    // {
    //     return (new AnnouncementMailable($notifiable, $this->announcement))->to($notifiable->email);
    // }

    /**
     * Get the fcm representation of the notification.
     */
    public function toFcmBulk(object $notifiable): array
    {
        return [
            'tokens' => $this->tokens,
            'notification' => [
                'title' => $this->notificationType->getLabel(),
                'body' => $this->announcement->subject,
            ],
            'data' => [
                'notifiable_type' => $this->notificationType->value,
                'notifiable_id' => $this->announcement->id,
            ],
        ];
    }
}
