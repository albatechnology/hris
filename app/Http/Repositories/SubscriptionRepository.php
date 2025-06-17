<?php

namespace App\Http\Repositories;

use App\Interfaces\Repositories\SubscriptionRepositoryInterface;
use App\Models\Subscription;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }
}
