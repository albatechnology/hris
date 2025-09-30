<?php

namespace App\Http\Services\User;

use App\Enums\MediaCollection;
use App\Enums\SubscriptionKey;
use App\Http\Requests\Api\User\RegisterRequest;
use App\Http\Services\BaseService;
use App\Http\Services\Subscription\ValidateSubscriptionService;
use App\Interfaces\Repositories\User\UserRepositoryInterface;
use App\Interfaces\Services\User\UserServiceInterface;
use App\Models\Company;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class UserService extends BaseService implements UserServiceInterface
{
    public function __construct(protected UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    private function generateNikFromCompany(string $joinDate, int $companyId)
    {
        $company = Company::find($companyId);
  
        if(!$company)
        {
            throw new Exception("Company not found");
        }
       
        $prefix = $company->employee_prefix;

         if(empty($prefix)){
            return null;
        }

        $year = date('y',strtotime($joinDate));

        $latestNik = User::where('nik','like',"{$prefix}{$year}%")
            ->orderBy('nik','desc')
            ->value('nik');

        if($latestNik){
            $lastNumber = (int) substr($latestNik,-3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }else{
            $newNumber = '001';
        }

        return "{$prefix}{$year}{$newNumber}";
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

            if(config('app.name') == 'SUNSHINE'){
                if(empty($request->nik) && $request->company_id){
                $requestNik = $this->generateNikFromCompany($request->join_date ?? now(), $request->company_id);
                $user->nik = $requestNik;
                $user->save();
            }
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
