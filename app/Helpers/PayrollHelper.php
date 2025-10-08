<?php

namespace App\Helpers;

use App\Models\RunPayrollUser;
use App\Models\User;

class PayrollHelper
{
    public static function isFirstTimePayroll(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        return RunPayrollUser::query()->where('user_id', $userId)
            ->whereHas('runPayroll', fn($q) => $q->release())
            ->limit(1)
            ->doesntExist();
    }
}
