<?php

namespace App\Notifications\Timeoff;

use App\Enums\NotificationType;
use App\Models\Timeoff;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestTimeoff extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private NotificationType $notificationType, private User $user, private Timeoff $timeoff)
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
        return ['database', 'fcm'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        if (date('Y-m-d', strtotime($this->timeoff->start_at)) == date('Y-m-d', strtotime($this->timeoff->end_at))) {
            $message = sprintf(
                $this->notificationType->getMessage(),
                date('d M Y', strtotime($this->timeoff->start_at)),
            );
        } else {
            $message = sprintf(
                $this->notificationType->getMessage(),
                date('d M Y', strtotime($this->timeoff->start_at)) . ' - ' . date('d M Y', strtotime($this->timeoff->end_at)),
            );
        }

        return [
            'type' => $this->notificationType->value,
            'message' => $message,
            'url_path' => $this->notificationType->getUrlPath(),
            'user_id' => $this->user->id,
            'model_id' => $this->timeoff->id
        ];
    }
    
    /**
     * Get the fcm representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        if (date('Y-m-d', strtotime($this->timeoff->start_at)) == date('Y-m-d', strtotime($this->timeoff->end_at))) {
            $body = sprintf(
                $this->notificationType->getMessage(),
                date('d M Y', strtotime($this->timeoff->start_at)),
            );
        } else {
            $body = sprintf(
                $this->notificationType->getMessage(),
                date('d M Y', strtotime($this->timeoff->start_at)) . ' - ' . date('d M Y', strtotime($this->timeoff->end_at)),
            );
        }

        return [
            'token' => $this->user->approval->fcm_token,
            'notification' => [
                'title' => $this->notificationType->getLabel(),
                'body' => $body,
                // 'body' => "You received Time-Off request {$this->timeoff->timeoffPolicy->type->getLabel()} from {$this->user->name} on " . \Carbon\Carbon::parse($this->timeoff->start_at)->format('d M y') . '.',
            ],
            'data' => [
                'notifiable_type' => $this->notificationType->value,
                'notifiable_id' => $this->timeoff->id,
            ],
        ];
    }
}
