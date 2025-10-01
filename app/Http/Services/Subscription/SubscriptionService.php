<?php

namespace App\Http\Services\Subscription;

use App\Enums\UserType;
use App\Events\Subscription\SubscriptionCreated;
use App\Http\Services\BaseService;
use App\Http\Services\Company\CompanyInitializeService;
use App\Interfaces\Repositories\Subscription\SubscriptionRepositoryInterface;
use App\Interfaces\Services\Subscription\SubscriptionServiceInterface;
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
            app(CompanyInitializeService::class)($company);

            $branch = $company->branches()->select('id', 'company_id')->firstOrFail();

            /** @var User $user */
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'group_id' => $group->id,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'live_attendance_id' => $company->liveAttendances()->select('id')->first()?->id ?? null,
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
                'group_id' => $group->id,
                ...$data,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        SubscriptionCreated::dispatch($subscription);

        return $subscription;
    }

    public function getQuotaInfo():array
    {
        $groupId = auth('sanctum')->user()->group_id;
        // dd($groupId);
        $usedUser = $this->repository->countUsedUsers($groupId);
        $usedCompany = $this->repository->countUsedCompanies($groupId);
        $quota = $this->repository->findAll(fn($q)=> $q->where('group_id', $groupId))->first();
        return [
            'user' => [
                'quota' => $quota?->max_users ?? 0,
                'used'  => $usedUser,
            ],
            'company' => [
                'quota' => $quota?->max_companies ?? 0,
                'used'  => $usedCompany,
            ],
        ];

    }
}
