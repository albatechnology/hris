<?php

namespace App\Http\Repositories\Subscription;

use App\Models\User;
use App\Models\Subscription;
use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Subscription\SubscriptionRepositoryInterface;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }

    public function countUsedUsers(int $id)
    {
        // return Cache::remember("used_users_{$id}", 3600, function() use ($id){
        //     // dd("Query jalan ke DB untuk group {$id}");
        //     return User::where('group_id', $id)
        //     ->whereNull('resign_date')
        //     ->count();
        // });
        $usedUser = User::where('group_id', $id)
            ->whereNull('resign_date')
            ->count();
        return $usedUser;
    }

    public function countUsedCompanies(int $id)
    {
        // return Cache::remember("used_companies_{$id}", 3600, function() use ($id){
        //     return Company::where('group_id', $id)
        //     ->count();
        // });
        $usedCompany = Company::where('group_id', $id)
            ->count();
        return $usedCompany;
    }

    public function getQuota(int $id)
    {
        // return Cache::remember("quota_{$id}", 3600, function() use ($id){
        //     return $this->model->where('group_id', $id)->first();
        // });
        return $this->model->where('group_id', $id)->first();
    }
}
