<?php

namespace App\Interfaces\Services\Subscription;

use App\Interfaces\Services\BaseServiceInterface;

interface SubscriptionServiceInterface extends BaseServiceInterface {
    public function getQuotaInfo();
}
