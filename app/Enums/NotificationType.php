<?php

namespace App\Enums;

enum NotificationType: string
{
    use BaseEnum;

    case ANNOUNCEMENT = 'announcement';

    case REQUEST_ATTENDANCE = 'request_attendance';
    case ATTENDANCE_APPROVED = 'attendance_approved';

    case REQUEST_TIMEOFF = 'request_timeoff';
    case TIMEOFF_APPROVED = 'timeoff_approved';

    case REQUEST_OVERTIME = 'request_overtime';
    case OVERTIME_APPROVED = 'overtime_approved';

    case REQUEST_TASK = 'request_task';
    case TASK_APPROVED = 'task_approved';

    case REQUEST_ADVANCED_LEAVE = 'request_advanced_leave';
    case ADVANCED_LEAVE_APPROVED = 'advanced_leave_approved';

    case REQUEST_CHANGE_DATA = 'request_change_data';
    case REQUEST_CHANGE_DATA_APPROVED = 'request_change_data_approved';

    case REQUEST_SCHEDULE = 'request_schedule';
    case REQUEST_SCHEDULE_APPROVED = 'request_schedule_approved';

    case REQUEST_SHIFT = 'request_shift';
    case REQUEST_SHIFT_APPROVED = 'request_shift_approved';

    case SETUP_PASSWORD = 'setup_password';

    public function getNotificationClass(): string
    {
        return match ($this) {
            self::ANNOUNCEMENT => \App\Notifications\Announcement\AnnouncementNotification::class,
            self::REQUEST_ATTENDANCE => \App\Notifications\Attendance\RequestAttendance::class,
            self::ATTENDANCE_APPROVED => \App\Notifications\Attendance\AttendanceApproved::class,
            self::REQUEST_TIMEOFF => \App\Notifications\Timeoff\RequestTimeoff::class,
            self::TIMEOFF_APPROVED => \App\Notifications\Timeoff\TimeoffApproved::class,
            self::REQUEST_OVERTIME => \App\Notifications\Overtime\RequestOvertime::class,
            self::OVERTIME_APPROVED => \App\Notifications\Overtime\OvertimeApproved::class,
            self::REQUEST_TASK => \App\Notifications\Task\RequestTask::class,
            self::TASK_APPROVED => \App\Notifications\Task\TaskApproved::class,
            self::REQUEST_ADVANCED_LEAVE => \App\Notifications\AdvancedLeave\RequestAdvancedLeave::class,
            self::ADVANCED_LEAVE_APPROVED => \App\Notifications\AdvancedLeave\AdvancedLeaveApproved::class,
            self::REQUEST_CHANGE_DATA => \App\Notifications\RequestChangeData\RequestChangeData::class,
            self::REQUEST_CHANGE_DATA_APPROVED => \App\Notifications\RequestChangeData\RequestChangeDataApproved::class,
            self::REQUEST_SCHEDULE => \App\Notifications\RequestSchedule\RequestSchedule::class,
            self::REQUEST_SCHEDULE_APPROVED => \App\Notifications\RequestSchedule\RequestScheduleApproved::class,
            self::REQUEST_SHIFT => \App\Notifications\RequestShift\RequestShift::class,
            self::REQUEST_SHIFT_APPROVED => \App\Notifications\RequestShift\RequestShiftApproved::class,
            self::SETUP_PASSWORD => \App\Notifications\User\SetupPasswordNotification::class,
        };
    }

    public function getMessage(): string
    {
        return match ($this) {
            self::REQUEST_ATTENDANCE => 'Requesting attendance (%s) for %s', // Monday, 01 Jan 2024
            self::ATTENDANCE_APPROVED => 'Your attendance (%s) request at %s on %s, has been %s.', // 09:00:00, Monday, 01 Jan 2024, approved/rejected
            self::REQUEST_TIMEOFF => 'Requesting time off (%s) for %s', // 01 feb - 12 feb 2024
            self::TIMEOFF_APPROVED => 'Your time off for %s has been %s.', // 1 March 2024/1 March 2024 to 10 March 2024, approved/rejected
            self::REQUEST_OVERTIME => 'Requesting overtime for %s',
            self::OVERTIME_APPROVED => 'Your overtime request has been %s.', // approved/rejected
            self::REQUEST_TASK => 'Requesting task for %s',
            self::TASK_APPROVED => 'Your task request has been %s.', // approved/rejected
            self::REQUEST_ADVANCED_LEAVE => 'Requesting advanced leave for %s %s', // 1 day/5 days
            self::ADVANCED_LEAVE_APPROVED => 'Your advanced leave request has been %s.', // approved/rejected
            self::REQUEST_CHANGE_DATA => 'Requesting change data',
            self::REQUEST_CHANGE_DATA_APPROVED => 'Your change data request has been %s.', // approved/rejected
            self::REQUEST_SCHEDULE => 'Requesting schedule',
            self::REQUEST_SCHEDULE_APPROVED => 'Your schedule request has been %s.', // approved/rejected
            self::REQUEST_SHIFT => 'Requesting shift',
            self::REQUEST_SHIFT_APPROVED => 'Your shift request has been %s.', // approved/rejected
            default => null,
        };
    }

    public function getUrlPath(): mixed
    {
        return match ($this) {
            self::REQUEST_ATTENDANCE => 'url/path',
            self::ATTENDANCE_APPROVED => 'url/path',
            self::REQUEST_TIMEOFF => 'url/path',
            self::TIMEOFF_APPROVED => 'url/path',
            self::REQUEST_OVERTIME => 'url/path',
            self::OVERTIME_APPROVED => 'url/path',
            self::REQUEST_ADVANCED_LEAVE => 'url/path',
            self::ADVANCED_LEAVE_APPROVED => 'url/path',
            default => null,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::REQUEST_ATTENDANCE => 'fa fa-check-double',
            self::ATTENDANCE_APPROVED => 'fa fa-check-double',
            self::REQUEST_TIMEOFF => 'fa fa-check-double',
            self::TIMEOFF_APPROVED => 'fa fa-check-double',
            self::REQUEST_OVERTIME => 'fa fa-check-double',
            self::OVERTIME_APPROVED => 'fa fa-check-double',
            self::REQUEST_ADVANCED_LEAVE => 'fa fa-check-double',
            self::ADVANCED_LEAVE_APPROVED => 'fa fa-check-double',
            default => null,
        };
    }
}
