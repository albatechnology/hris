<?php

namespace App\Http\Services\Subscription;

use App\Enums\SubscriptionKey;
use App\Models\Company;
use App\Models\Group;
use App\Models\Subscription;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidateSubscriptionService
{
    private ?Subscription $subscription;

    public function __construct(protected Group|int $groupId, protected SubscriptionKey $subscriptionKey)
    {
        if ($groupId instanceof Group) $groupId = $groupId->id;

        $this->subscription = Subscription::select('id', 'active_end_date', $this->subscriptionKey->value)->where('group_id', $groupId)->first();
    }

    public function __invoke()
    {
        return $this->validate();
    }

    public function validate(): bool
    {
        if (config('app.name') != 'LUMORA' || (config('app.env') == 'production') && in_array($this->groupId, [1, 3])) return true;

        if (!$this->subscription) throw new NotFoundHttpException('Subscription not found');

        if (date('Y-m-d') > $this->subscription->active_end_date) {
            throw new AccessDeniedHttpException('Subscription expired');
        }

        if ($this->getTotalData() >= $this->subscription->{$this->subscriptionKey->value}) {
            throw $this->subscriptionKey->exception();
        }
        return true;
    }

    private function getTotalData(): int
    {
        if ($this->subscriptionKey->is(SubscriptionKey::COMPANIES)) {
            return Company::select('id')->where('group_id', $this->groupId)->count();
        } elseif ($this->subscriptionKey->is(SubscriptionKey::USERS)) {
            return User::select('id')->where('group_id', $this->groupId)->whereNull('resign_date')->count();
        }

        throw new BadRequestHttpException('Invalid subscription key');
    }
}
