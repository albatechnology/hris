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
                return $data;
                // if (isset($data['model_id']) && $data['model_id'] != '') {
                //     $notificationType = NotificationType::from($data['type']);

                //     if (in_array($notificationType, [NotificationType::REQUEST_ATTENDANCE, NotificationType::ATTENDANCE_APPROVED])) {
                //         $data['attendance_detail'] = AttendanceDetail::find($data['model_id']);
                //     } elseif (in_array($notificationType, [NotificationType::REQUEST_TIMEOFF, NotificationType::TIMEOFF_APPROVED])) {
                //         $data['timeoff'] = Timeoff::select('id', 'timeoff_policy_id', 'request_type', 'start_at', 'end_at', 'reason')
                //             ->with(['timeoffPolicy' => fn ($q) => $q->select('id', 'name')])
                //             ->find($data['model_id'])->append('total_days');
                //     } else {
                //         $data['model'] = null;
                //     }

                //     unset($data['model_id']);
                // }
            },
        );
    }
}
