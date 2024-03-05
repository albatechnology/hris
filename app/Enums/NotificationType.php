<?php

namespace App\Enums;

enum NotificationType: string
{
    use BaseEnum;

    case REQUEST_ATTENDANCE = 'request_attendance';
    case ATTENDANCE_APPROVED = 'attendance_approved';

    case REQUEST_TIMEOFF = 'request_timeoff';
    case TIMEOFF_APPROVED = 'timeoff_approved';

    case REQUEST_OVERTIME = 'request_overtime';
    case OVERTIME_APPROVED = 'overtime_approved';

    public function getNotificationClass(): string
    {
        return match ($this) {
            self::REQUEST_ATTENDANCE => \App\Notifications\Attendance\RequestAttendance::class,
            self::ATTENDANCE_APPROVED => \App\Notifications\Attendance\AttendanceApproved::class,
            self::REQUEST_TIMEOFF => \App\Notifications\Timeoff\NeedTimeoffApproval::class,
            self::TIMEOFF_APPROVED => \App\Notifications\Timeoff\TimeoffApproved::class,
        };
    }

    public function getMessage(): string
    {
        return match ($this) {
            self::REQUEST_ATTENDANCE => '%s membutuhkan approval attendance dari anda',
            self::ATTENDANCE_APPROVED => '%s %s request attendance anda', // difa menyetujui/menolak attendance anda
            self::REQUEST_TIMEOFF => '%s membutuhkan approval timeoff dari anda',
            self::TIMEOFF_APPROVED => '%s %s request timeoff anda', // difa menyetujui/menolak timeoff anda
            self::REQUEST_OVERTIME => '%s membutuhkan approval overtime dari anda',
            self::OVERTIME_APPROVED => '%s %s request overtime anda', // difa menyetujui/menolak overtime anda
            default => null,
        };
    }

    public function getUrlPath(): mixed
    {
        return match ($this) {
            self::REQUEST_ATTENDANCE => 'approvals/%s/edit',
            self::ATTENDANCE_APPROVED => 'approvals/%s/edit',
            self::REQUEST_TIMEOFF => 'approvals/%s/edit',
            self::TIMEOFF_APPROVED => 'approvals/%s/edit',
            self::REQUEST_OVERTIME => 'approvals/%s/edit',
            self::OVERTIME_APPROVED => 'approvals/%s/edit',
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
            default => null,
        };
    }
}
