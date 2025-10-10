<?php

namespace App\Enums;

use App\Exceptions\CompanyLimitReachedException;
use App\Exceptions\UserLimitReachedException;

enum SubscriptionKey: string
{
    use BaseEnum;

    case COMPANIES = 'max_companies';
    case USERS = 'max_users';

    public function exception(): \Throwable
    {
        return match ($this) {
            self::COMPANIES => new CompanyLimitReachedException(),
            self::USERS => new UserLimitReachedException(),
        };
    }
}
