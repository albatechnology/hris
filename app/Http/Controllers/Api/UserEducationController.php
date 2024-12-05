<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Http\Requests\Api\UserEducation\StoreRequest;
use App\Http\Resources\UserEducation\UserEducationResource;
use App\Models\User;
use App\Models\UserEducation;
use Exception;
use Spatie\QueryBuilder\QueryBuilder;

class UserEducationController extends BaseController
{
    public function index(int $id)
    {
        $user = User::findTenanted($id);
        $data = QueryBuilder::for(UserEducation::where('user_id', $user->id))
            ->allowedFilters([
                'type',
                'level',
                'name',
                'institution_name',
                'majors',
                'start_date',
                'end_date',
                'expired_date',
                'score',
                'fee',
            ])
            ->allowedSorts([
                'id',
                'name',
                'institution_name',
                'majors',
                'start_date',
                'end_date',
                'expired_date',
                'score',
                'fee',
                'created_at',
            ])
            ->paginate($this->per_page);

        return UserEducationResource::collection($data);
    }

    public function show(int $id, UserEducation $education)
    {
        $user = User::findTenanted($id);
        return new UserEducationResource($education);
    }

    public function store(int $id, StoreRequest $request)
    {
        $user = User::findTenanted($id);
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

    public function update(int $id, StoreRequest $request, UserEducation $education)
    {
        $user = User::findTenanted($id);
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

    public function destroy(int $id, UserEducation $education)
    {
        $user = User::findTenanted($id);
        $education->delete();

        return $this->deletedResponse();
    }
}
