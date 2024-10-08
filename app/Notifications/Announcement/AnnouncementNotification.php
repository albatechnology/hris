<?php

namespace App\Notifications\Announcement;

use App\Enums\NotificationType;
use App\Mail\AnnouncementMailable;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
  use Queueable;

  /**
   * Create a new notification instance.
   */
  public function __construct(private NotificationType $notificationType, private User $user, private Announcement $announcement)
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
    $via = [];

    if ($this->announcement->is_send_email) {
      $via[] = 'mail';
    }

    if ($this->user->fcm_token) {
      $via[] = 'fcm';
    }

    return $via;
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): AnnouncementMailable
  {
    return (new AnnouncementMailable($notifiable, $this->announcement))->to($notifiable->email);
  }

  /**
   * Get the fcm representation of the notification.
   */
  public function toFcm(object $notifiable): array
  {
    return [
      'token' => $this->user->fcm_token,
      'notification' => [
        'title' => $this->notificationType->getLabel(),
        'body' => $this->announcement->content,
      ],
      'data' => [
        'notifiable_type' => $this->notificationType->value,
        'notifiable_id' => $this->announcement->id,
      ],
    ];
  }
}
