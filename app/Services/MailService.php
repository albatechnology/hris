<?php

namespace App\Services;

use App\Enums\FileVisibility;
use App\Enums\UserType;
use App\Models\File;
use App\Models\User;

class MailService
{
    public static function canGiveAccess(File $file, User $user = null): bool
    {
        if (!$user) $user = auth()->user();

        if($user->type->is(UserType::PENGUNJUNG)) return false;

        if ($user->is_admin) return true;
        if ($user->id == $file->user_id || $user->id == $file->sender_id) return true;
        return false;
    }

    public static function canDownload(File $file, User $user = null): bool
    {
        if (!$user) $user = auth()->user();

        if ($file->visibility->is(FileVisibility::PUBLIC)) return true;

        if ($user->is_admin) return true;

        $canApprove = $file->approvals()->where('id', $user->id)->exists();
        $canAccess = $file->userFileAccesses()->where('user_id', $user->id)->whereAccessActive()->exists();
        if ($user->id == $file->user_id || $user->id == $file->sender_id || $user->id == $file->receiver_id || $canApprove || $canAccess) return true;
        if (!$file->visibility->is(FileVisibility::PUBLIC) && $user->type->is(UserType::PENGUNJUNG)) return false;
        return false;
    }
}
