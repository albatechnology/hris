<?php

namespace App\Notifications\Reimbursement;

use App\Enums\NotificationType;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestReimbursement extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private NotificationType $notificationType, private User $user, private Reimbursement $reimbursement)
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
        return [
            'type' => $this->notificationType->value,
            'message' => sprintf($this->notificationType->getMessage(), $this->reimbursement->duration_text),
            // 'url_path' => $this->notificationType->getUrlPath(),
            'user_id' => $this->user->id,
            'model_id' => $this->reimbursement->id
        ];
    }

    /**
     * Get the fcm representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        $body = sprintf($this->notificationType->getMessage(), $this->reimbursement->duration_text);

        return [
            'token' => $this->user->fcm_token,
            'notification' => [
                'title' => $this->notificationType->getLabel(),
                'body' => $body,
            ],
            'data' => [
                'notifiable_type' => $this->notificationType->value,
                'notifiable_id' => $this->reimbursement->id,
            ],
        ];
    }
}
