<?php

namespace App\Broadcasting;

use App\Models\User;
use Exception;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FcmBulkChannel
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
        $data = $notification->toFcmBulk($notifiable);
        try {
            $message = CloudMessage::new()->withData($data['data']);
            // ->withNotification($data['notification'])

            if (isset($data['notification'])) {
                $message = $message->withNotification($data['notification']);
            }

            $this->fcm->sendMulticast($message, $data['tokens']);
        } catch (Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
        }
    }
}
