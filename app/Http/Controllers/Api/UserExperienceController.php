<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserExperience\StoreRequest;
use App\Http\Resources\UserExperience\UserExperienceResource;
use App\Models\User;
use App\Models\UserExperience;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserExperienceController extends BaseController
{
    public function index(int $userid)
    {
        $user = User::findTenanted($userid);
        $data = QueryBuilder::for(UserExperience::tenanted()->where('user_id', $user->id))
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                'company',
                'department',
                'position',
                'start_date',
                'end_date',
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'company',
                'department',
                'position',
                'start_date',
                'end_date',
                'created_at',
            ])
            ->paginate($this->per_page);

        return UserExperienceResource::collection($data);
    }

    public function show(int $userid, UserExperience $experience)
    {
        $user = User::findTenanted($userid);
        return new UserExperienceResource($experience);
    }

    public function store(int $userid, StoreRequest $request)
    {
        $user = User::findTenanted($userid);
        $experience = $user->experiences()->create($request->validated());

        return new UserExperienceResource($experience);
    }

    public function update(int $userid, StoreRequest $request, UserExperience $experience)
    {
        $user = User::findTenanted($userid);
        $experience->update($request->validated());

        return new UserExperienceResource($experience);
    }

    public function destroy(int $userid, UserExperience $experience)
    {
        $user = User::findTenanted($userid);
        $experience->delete();

        return $this->deletedResponse();
    }
}
