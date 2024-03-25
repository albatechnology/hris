<?php

namespace App\Models;

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
                    }
                }

                return $data;
            },
        );
    }
}
