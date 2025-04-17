<?php

namespace App\Notifications\Reprimand;

use App\Enums\NotificationType;
use App\Models\Reprimand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReprimandNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private NotificationType $notificationType, private Reprimand $reprimand)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['fcm'];
    }

    /**
     * Get the fcm representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'token' => $notifiable->fcm_token,
            'notification' => [
                'title' => $this->notificationType->getLabel(),
                'body' => $this->reprimand->notes,
            ],
            'data' => [
                'notifiable_type' => $this->notificationType->value,
                'notifiable_id' => $this->reprimand->id,
            ],
        ];
    }
}
