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
        $attendanceDetail = $event->attendanceDetail->load([
            'attendance' => fn ($q) => $q->select('id', 'user_id')
                ->with('user', fn ($q) => $q->select('id', 'approval_id')->with('approval', fn ($q) => $q->select('id'))),
        ]);

        if (!$attendanceDetail->type->is(AttendanceType::MANUAL)) return;

        $notificationType = NotificationType::REQUEST_ATTENDANCE;
        $attendanceDetail->attendance->user?->approval?->notify(new ($notificationType->getNotificationClass())($notificationType, $attendanceDetail->attendance->user, $attendanceDetail));
    }
}
