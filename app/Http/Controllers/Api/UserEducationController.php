<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Http\Requests\Api\UserEducation\StoreRequest;
use App\Http\Resources\UserEducation\UserEducationResource;
use App\Models\User;
use App\Models\UserEducation;
use Exception;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserEducationController extends BaseController
{
    public function index(User $user)
    {
        $data = QueryBuilder::for(UserEducation::where('user_id', $user->id))
            ->allowedFilters([
                'type', 'level', 'name', 'institution_name', 'majors', 'start_date', 'end_date', 'expired_date', 'score', 'fee',
            ])
            ->allowedSorts([
                'id', 'name', 'institution_name', 'majors', 'start_date', 'end_date', 'expired_date', 'score', 'fee', 'created_at',
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
        try {
            $education = $user->educations()->create($request->validated());

            $mediaCollection = MediaCollection::USER_EDUCATION->value;
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $education->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new UserEducationResource($education);
    }

    public function update(User $user, StoreRequest $request, UserEducation $education)
    {
        try {
            $education->update($request->validated());

            $mediaCollection = MediaCollection::USER_EDUCATION->value;
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $education->clearMediaCollection($mediaCollection);
                $education->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new UserEducationResource($education);
    }

    public function destroy(User $user, UserEducation $education)
    {
        $education->delete();

        return $this->deletedResponse();
    }
}
