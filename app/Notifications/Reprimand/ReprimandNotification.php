<?php

namespace App\Notifications\Reprimand;

use App\Enums\NotificationType;
use App\Mail\Reprimand\ReprimandMail;
use App\Models\Reprimand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReprimandNotification extends Notification implements ShouldQueue
{
    use Queueable;
    private string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(private NotificationType $notificationType, private Reprimand $reprimand)
    {
        $this->message = sprintf($this->notificationType->getMessage(), $reprimand->type->getDescription());
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [
            // 'database',
            // 'fcm',
            // 'mail'
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->notificationType->value,
            'message' => $this->message,
            'url_path' => $this->notificationType->getUrlPath(),
            'user_id' => $notifiable->id,
            'model_id' => $this->reprimand->id
        ];
    }

    public function toMail(object $notifiable)
    {
        return (new ReprimandMail($notifiable, $this->reprimand))->to($notifiable->email);
        // return (new $this->mailClass($notifiable, $this->reprimand))->to($notifiable->email);
    }

    /**
     * Get the fcm representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'token' => $notifiable->fcm_token,
            'notification' => [
                'title' => "TEST REPRIMAND DEVELOPMENT",
                'body' => "ABAIKAN PESAN INI",
                // 'body' => $this->message,
            ],
            'data' => [
                'notifiable_type' => $this->notificationType->value,
                'notifiable_id' => $this->reprimand->id,
            ],
        ];
    }
}
