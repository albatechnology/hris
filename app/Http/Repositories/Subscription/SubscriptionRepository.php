<?php

namespace App\Http\Repositories\Subscription;

use App\Models\User;
use App\Models\Subscription;
use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Subscription\SubscriptionRepositoryInterface;
use App\Models\Company;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }

    public function countUsedUsers(int $id)
    {
         $usedUser = User::where('group_id', $id)
            ->whereNull('resign_date')
            ->count();
        return $usedUser;
    }

    public function countUsedCompanies(int $id)
    {
        $usedCompany = Company::where('group_id', $id)
            ->count();
        return $usedCompany;
    }
}
