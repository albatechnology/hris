<?php

namespace App\Notifications\AbsenceReminder;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AbsenceReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private string $message)
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
                'title' => "Pengingat Absensi",
                'body' => $this->message,
            ],
            'data' => [
                'notifiable_type' => "absence_reminder",
                // 'notifiable_id' => $this->reprimand->id,
            ],
        ];
    }
}
