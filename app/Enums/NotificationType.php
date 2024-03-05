<?php

namespace App\Enums;

enum NotificationType: string
{
    use BaseEnum;

    case NEED_TIMEOFF_APPROVAL = 'need_timeoff_approval';
    case TIMEOFF_APPROVED = 'timeoff_approved';

    public function getNotificationClass(): string
    {
        return match ($this) {
            self::NEED_TIMEOFF_APPROVAL => \App\Notifications\Timeoff\NeedTimeoffApproval::class,
            self::TIMEOFF_APPROVED => \App\Notifications\Timeoff\TimeoffApproved::class,
        };
    }

    public function getMessage(): string
    {
        return match ($this) {
            self::NEED_TIMEOFF_APPROVAL => '%s membutuhkan approval timeoff dari anda',
            self::TIMEOFF_APPROVED => '%s %s timeoff dari anda', // difa menyetujui/menolak timeoff anda
            default => $this->value,
        };
    }

    public function getUrlPath(): mixed
    {
        return match ($this) {
            self::NEED_TIMEOFF_APPROVAL => 'approvals/%s/edit',
            self::TIMEOFF_APPROVED => 'approvals/%s/edit',
            // default => $this->value,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::NEED_TIMEOFF_APPROVAL => 'fa fa-check-double',
            self::TIMEOFF_APPROVED => 'fa fa-check-double',
        };
    }
}
