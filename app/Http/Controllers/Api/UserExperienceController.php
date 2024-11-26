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
    public function index(User $user)
    {
        $data = QueryBuilder::for(UserExperience::where('user_id', $user->id))
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                'company', 'department', 'position', 'start_date', 'end_date',
            ])
            ->allowedSorts([
                'id', 'user_id', 'company', 'department', 'position', 'start_date', 'end_date', 'created_at',
            ])
            ->paginate($this->per_page);

        return UserExperienceResource::collection($data);
    }

    public function show(User $user, UserExperience $experience)
    {
        return new UserExperienceResource($experience);
    }

    public function store(User $user, StoreRequest $request)
    {
        $experience = $user->experiences()->create($request->validated());

        return new UserExperienceResource($experience);
    }

    public function update(User $user, StoreRequest $request, UserExperience $experience)
    {
        $experience->update($request->validated());

        return new UserExperienceResource($experience);
    }

    public function destroy(User $user, UserExperience $experience)
    {
        $experience->delete();

        return $this->deletedResponse();
    }
}
