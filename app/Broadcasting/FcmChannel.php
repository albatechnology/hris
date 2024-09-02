<?php

namespace App\Broadcasting;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FcmChannel
{
    protected $fcm;

    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        $serviceAccountPath = config('firebase.projects.' . config('firebase.default') . '.credentials');

        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $this->fcm = $factory->createMessaging();
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user): array|bool
    {
        //
    }

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $data = $notification->toFcm($notifiable);

        if($data['token']){
            $data = CloudMessage::withTarget('token', $data['token'])
                ->withNotification($data['notification'])
                ->withData($data['data']);
    
            $this->fcm->send($data);
        }
    }
}
