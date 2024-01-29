<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserEducation\StoreRequest;
use App\Http\Resources\UserEducation\UserEducationResource;
use App\Models\User;
use App\Models\UserEducation;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserEducationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // // $this->middleware('permission:user_access', ['only' => ['index', 'show', 'restore']]);
        // $this->middleware('permission:user_access', ['only' => ['restore']]);
        // $this->middleware('permission:user_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:user_create', ['only' => 'store']);
        // $this->middleware('permission:user_edit', ['only' => 'update']);
        // $this->middleware('permission:user_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(User $user)
    {
        $data = QueryBuilder::for(UserEducation::where('user_id', $user->id))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                'company', 'department', 'position', 'start_date', 'end_date'
            ])
            ->allowedSorts([
                'id', 'user_id', 'company', 'department', 'position', 'start_date', 'end_date', 'created_at'
            ])
            ->paginate($this->per_page);

        return UserEducationResource::collection($data);
    }

    public function show(User $user, UserEducation $education)
    {
        return new UserEducationResource($education);
    }

    public function store(User $user, StoreRequest $request)
    {
        $education = $user->educations()->create($request->validated());
        return new UserEducationResource($education);
    }

    public function update(User $user, StoreRequest $request, UserEducation $education)
    {
        $education->update($request->validated());
        return new UserEducationResource($education);
    }

    public function destroy(User $user, UserEducation $education)
    {
        $education->delete();
        return $this->deletedResponse();
    }
}
