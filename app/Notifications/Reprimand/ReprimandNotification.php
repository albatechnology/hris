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
    private string $message;
    private string $body;

    /**
     * Create a new notification instance.
     */
    public function __construct(private NotificationType $notificationType, private Reprimand $reprimand)
    {
        $this->message = $this->notificationType->is(NotificationType::REPRIMAND_WATCHER) ? sprintf($this->notificationType->getMessage(), $this->reprimand->user->name, $this->reprimand->type->value) : sprintf($this->notificationType->getMessage(), $this->reprimand->type->value);

        $this->body = $this->notificationType->is(NotificationType::REPRIMAND_WATCHER) ?
            $this->message : ($this->reprimand->notes ? $this->reprimand->notes : $this->message);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
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

    /**
     * Get the fcm representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'token' => $notifiable->fcm_token,
            'notification' => [
                'title' => "Reprimand",
                'body' => $this->body,
            ],
            'data' => [
                'notifiable_type' => $this->notificationType->value,
                'notifiable_id' => $this->reprimand->id,
            ],
        ];
    }
}
