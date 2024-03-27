<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Notifications\DatabaseNotification as NotificationsDatabaseNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DatabaseNotification extends NotificationsDatabaseNotification
{
    protected function data(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $data = json_decode($value, true);
                if (isset($data['user_id']) && $data['user_id'] != '') {
                    $user = User::select('id', 'name')->find($data['user_id']);
                    $user->image = $user->image;
                    if ($user) {
                        $data['user'] = $user;
                        unset($data['user_id']);
                    }
                }

                if (isset($data['model_id']) && $data['model_id'] != '') {
                    $notificationType = NotificationType::from($data['type']);
                    $model = match ($notificationType) {
                        NotificationType::REQUEST_ATTENDANCE => AttendanceDetail::find($data['model_id']),
                    };

                    $data['model'] = $model;
                    unset($data['model_id']);
                }

                return $data;
            },
        );
    }
}
