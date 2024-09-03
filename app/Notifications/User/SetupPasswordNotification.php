<?php

namespace App\Notifications\User;

use App\Enums\NotificationType;
use App\Mail\SetupPasswordMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SetupPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */


    public function __construct(private NotificationType $notificationType) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): SetupPasswordMailable
    {
        return (new SetupPasswordMailable($notifiable))->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
