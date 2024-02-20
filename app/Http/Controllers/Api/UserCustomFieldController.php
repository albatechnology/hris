<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserCustomField\StoreRequest;
use App\Http\Requests\Api\UserCustomField\UpdateRequest;
use App\Http\Resources\UserCustomField\UserCustomFieldResource;
use App\Models\CustomField;
use App\Models\User;
use App\Models\UserCustomField;
use Illuminate\Http\Response;

class UserCustomFieldController extends BaseController
{
    public function index(User $user)
    {
        $customFields = CustomField::tenanted()->get();

        $customFields = $customFields->map(function ($customField) use ($user) {
            $userCustomField = $user->customFields()->where('custom_field_id', $customField->id)->first();
            $customField->custom_field_id = $customField->id;
            $customField->id = null;
            $customField->value = null;

            if ($userCustomField) {
                $customField->id = $userCustomField->id;
                $customField->value = $userCustomField->value;
            }

            return $customField;
        });

        return UserCustomFieldResource::collection($customFields);
    }

    public function show(User $user, UserCustomField $customField)
    {
        $userCustomField = $user->customFields()->where('id', $customField->id)->firstOrFail();
        return new UserCustomFieldResource($userCustomField);
    }

    public function store(User $user, StoreRequest $request)
    {
        $userCustomField = $user->customFields()->where('custom_field_id', $request->custom_field_id)->exists();
        if ($userCustomField) return $this->errorResponse('Custom field already exists', code: Response::HTTP_BAD_REQUEST);

        $userCustomField = $user->customFields()->create($request->validated());

        return new UserCustomFieldResource($userCustomField);
    }

    public function update(User $user, UserCustomField $customField, UpdateRequest $request)
    {
        $userCustomField = $user->customFields()->where('id', $customField->id)->firstOrFail();
        $userCustomField->update($request->validated());

        return (new UserCustomFieldResource($userCustomField))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
