<?php

namespace App\Http\Services\User;

use App\Enums\MediaCollection;
use App\Enums\SubscriptionKey;
use App\Http\Requests\Api\User\RegisterRequest;
use App\Http\Services\BaseService;
use App\Http\Services\Subscription\ValidateSubscriptionService;
use App\Interfaces\Repositories\User\UserRepositoryInterface;
use App\Interfaces\Services\User\UserServiceInterface;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class UserService extends BaseService implements UserServiceInterface
{
    public function __construct(protected UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function register(RegisterRequest $request): User
    {
        (new ValidateSubscriptionService($request->group_id ?? auth()->user()->group_id, SubscriptionKey::USERS))();

        DB::beginTransaction();
        try {
            $user = User::create($request->validated());

            if ($request->hasFile('photo_profile') && $request->file('photo_profile')->isValid()) {
                $mediaCollection = MediaCollection::USER->value;
                $user->addMediaFromRequest('photo_profile')->toMediaCollection($mediaCollection);
            }

            $user->detail()->create($request->validated());
            $user->payrollInfo()->create($request->validated());
            $user->positions()->createMany($request->positions ?? []);
            $user->roles()->syncWithPivotValues($request->role_ids ?? [], ['group_id' => $user->group_id]);
            $user->schedules()->sync([
                'schedule_id' => $request->schedule_id
            ]);

            $companyIds = collect($request->company_ids ?? []);
            if ($user->company_id) {
                $companyIds->push($user->company_id);
            }
            $companyIds = $companyIds->unique()->values()
                ->map(function ($companyId) {
                    return ['company_id' => $companyId];
                })->all();
            $user->companies()->createMany($companyIds);

            $branchIds = collect($request->branch_ids ?? []);
            if ($user->branch_id) {
                $branchIds->push($user->branch_id);
            }
            $branchIds = $branchIds->unique()->values()
                ->map(function ($branchId) {
                    return ['branch_id' => $branchId];
                })->all();
            $user->branches()->createMany($branchIds);

            if ($request->overtime_id) {
                DB::table('user_overtimes')->insert([
                    'user_id' => $user->id,
                    'overtime_id' => $request->overtime_id
                ]);
            }

            if (empty($request->password)) {
                $notificationType = \App\Enums\NotificationType::SETUP_PASSWORD;
                $user->notify(new ($notificationType->getNotificationClass())($notificationType));
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $user;
    }
}
