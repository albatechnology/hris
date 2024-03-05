<?php

namespace App\Listeners\Attendance;

use App\Enums\AttendanceType;
use App\Enums\NotificationType;
use App\Events\Attendance\AttendanceRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RequestAttendanceNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AttendanceRequested $event): void
    {
        $attendance = $event->attendance;
        if (!$attendance->details->contains('type', AttendanceType::MANUAL)) return;

        $notificationType = NotificationType::REQUEST_ATTENDANCE;
        $attendance->user->manager?->notify(new ($notificationType->getNotificationClass())($notificationType, $attendance->user));
    }
}
