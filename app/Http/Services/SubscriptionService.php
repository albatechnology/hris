<?php

namespace App\Http\Services;

use App\Enums\UserType;
use App\Events\Subscription\SubscriptionCreated;
use App\Interfaces\Repositories\SubscriptionRepositoryInterface;
use App\Interfaces\Services\SubscriptionServiceInterface;
use App\Models\Company;
use App\Models\Group;
use App\Models\Subscription;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService extends BaseService implements SubscriptionServiceInterface
{
    public function __construct(protected SubscriptionRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): Subscription
    {
        DB::beginTransaction();
        try {
            $group = Group::create([
                'name' => $data['company_name'],
            ]);

            /** @var Company $company */
            $company = $group->companies()->create([
                'name' => $data['company_name'],
                'address' => $data['company_address'],
                'country_id' => 1,
            ]);

            $branch = $company->branches()->create([
                'name' => $company->name,
                'country' => $company->country,
                'province' => $company->province,
                'city' => $company->city,
                'zip_code' => $company->zip_code,
                'lat' => $company->lat,
                'lng' => $company->lng,
                'address' => $company->address,
            ]);

            /** @var User $user */
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'group_id' => $group->id,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'password' => Str::random(10),
                'type' => UserType::ADMIN,
            ]);

            $company->settings()->update([
                'value' => $user->id
            ]);

            $user->companies()->create([
                'company_id' => $company->id,
            ]);

            $user->branches()->create([
                'branch_id' => $branch->id,
            ]);

            $user->detail()->create();
            $user->payrollInfo()->create();
            $user->userBpjs()->create();

            if ($company->overtimes->count()) {
                $user->overtimes()->attach([
                    'overtime_id' => $company->overtimes[0]->id,
                ]);
            }

            if ($company->schedules->count()) {
                $user->schedules()->attach([
                    'overtime_id' => $company->schedules[0]->id,
                ]);
            }

            $subscription = $this->repository->create([
                'user_id' => $user->id,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        SubscriptionCreated::dispatch($subscription);

        return $subscription;
    }
}
