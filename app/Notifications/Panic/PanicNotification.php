<?php

namespace App\Notifications\Panic;

use App\Models\Panic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PanicNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private Panic $panic)
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
                'title' => "Emergency",
                'body' => $this->panic->user->name . " sedang dalam keadaan darurat",
            ],
            'data' => [
                'notifiable_type' => "emergency",
                'notifiable_id' => $this->panic->id,
                'lat' => $this->panic->lat,
                'lng' => $this->panic->lng,
                'user_id' => $this->panic->user_id,
            ],
        ];
    }
}
