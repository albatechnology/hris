<?php

namespace App\Broadcasting;

use App\Models\User;
use Exception;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FcmChannel
{
    protected $fcm;

    /**
     * @method array toFcm(object $notifiable)
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

        try {
            if ($data['token']) {
                $data = CloudMessage::withTarget('token', $data['token'])
                    ->withNotification($data['notification'])
                    ->withData($data['data']);

                $this->fcm->send($data);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
        }
    }
}
