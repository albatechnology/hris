<?php

namespace App\Enums;

enum UserType: string
{
    use BaseEnum;

    case SUPER_ADMIN = 'super_admin';
    case ADMINISTRATOR = 'administrator';
    case ADMIN = 'admin';
    case USER = 'user';

    public function hasPermission(string $access, string $permission): bool
    {
        return true;
    }

    // public function getNotificationClass(): string
    // {
    //     return match ($this) {
    //         self::NEED_FILE_APPROVAL => \App\Notifications\NeedFileApproval::class,
    //         self::REMOVE_FILE_APPROVAL => \App\Notifications\RemoveFileApproval::class,
    //         self::GIVE_FILE_ACCESS => \App\Notifications\GiveFileAccess::class,
    //         self::REMOVE_FILE_ACCESS => \App\Notifications\RemoveFileAccess::class,
    //         self::FILE_APPROVED => \App\Notifications\FileApproved::class,
    //         self::FILE_REJECTED => \App\Notifications\FileApproved::class,
    //         default => $this->value,
    //     };
    // }

    // public function getMessage(): string
    // {
    //     return match ($this) {
    //         self::NEED_FILE_APPROVAL => 'File %s Membutuhkan approval anda',
    //         self::REMOVE_FILE_APPROVAL => 'Anda dihapus dari list approval file %s',
    //         self::GIVE_FILE_ACCESS => 'Anda diberi akses terhadap file %s',
    //         self::REMOVE_FILE_ACCESS => 'Akses terhadap file %s dihapus',
    //         self::FILE_APPROVED => 'File %s approved by %s',
    //         self::FILE_REJECTED => 'File %s rejected by %s',
    //         default => $this->value,
    //     };
    // }

    // public function getUrlPath(): mixed
    // {
    //     return match ($this) {
    //         self::NEED_FILE_APPROVAL => 'approvals/%s/edit',
    //         self::REMOVE_FILE_APPROVAL => null,
    //         self::GIVE_FILE_ACCESS => 'mails/%s/%s',
    //         self::REMOVE_FILE_ACCESS => null,
    //         self::FILE_APPROVED => 'mails/%s/%s',
    //         self::FILE_REJECTED => 'mails/%s/%s',
    //         default => $this->value,
    //     };
    // }

    // public function getIcon(): string
    // {
    //     return match ($this) {
    //         self::NEED_FILE_APPROVAL => 'fa fa-check-double',
    //         self::REMOVE_FILE_APPROVAL => 'fa fa-trash',
    //         self::GIVE_FILE_ACCESS => 'fa fa-check',
    //         self::REMOVE_FILE_ACCESS => 'fa fa-trash',
    //         self::FILE_APPROVED => 'fa fa-check',
    //         self::FILE_REJECTED => 'fa fa-ban',
    //     };
    // }
}
