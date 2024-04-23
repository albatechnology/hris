<?php

namespace App\Notifications\Timeoff;

use App\Enums\ApprovalStatus;
use App\Enums\NotificationType;
use App\Models\Timeoff;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeoffApproved extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private NotificationType $notificationType, private User $user, private ApprovalStatus $approvalStatus, private Timeoff $timeoff)
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
        return ['database'];
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
            $message = sprintf($this->notificationType->getMessage(), date('l, d M Y', strtotime($this->timeoff->start_at)), $this->approvalStatus->value);
        } else {
            $message = sprintf(
                $this->notificationType->getMessage(),
                date('l, d M Y', strtotime($this->timeoff->start_at)) . ' to ' . date('l, d M Y', strtotime($this->timeoff->end_at)),
                $this->approvalStatus->value
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
}
