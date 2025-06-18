<?php

namespace App\Http\Repositories\Subscription;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Subscription\SubscriptionRepositoryInterface;
use App\Models\Subscription;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }
}
