<?php

namespace App\Interfaces\Repositories\Subscription;

use App\Interfaces\Repositories\BaseRepositoryInterface;

interface SubscriptionRepositoryInterface extends BaseRepositoryInterface {
    public function countUsedUsers(int $id);
    public function countUsedCompanies(int $id);
}
