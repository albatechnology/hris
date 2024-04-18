<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserContact\StoreRequest;
use App\Http\Resources\UserContact\UserContactResource;
use App\Models\User;
use App\Models\UserContact;
use Spatie\QueryBuilder\QueryBuilder;

class UserContactController extends BaseController
{
    public function index(User $user)
    {
        $data = QueryBuilder::for(UserContact::where('user_id', $user->id))
            ->allowedFilters([
                'type', 'name', 'id_number', 'email', 'phone',
            ])
            ->allowedSorts([
                'id', 'type', 'name', 'id_number', 'email', 'phone', 'created_at',
            ])
            ->paginate($this->per_page);

        return UserContactResource::collection($data);
    }

    public function show(User $user, UserContact $contact)
    {
        return new UserContactResource($contact);
    }

    public function store(User $user, StoreRequest $request)
    {
        $contact = $user->contacts()->create($request->validated());

        return new UserContactResource($contact);
    }

    public function update(User $user, StoreRequest $request, UserContact $contact)
    {
        $contact->update($request->validated());

        return new UserContactResource($contact);
    }

    public function destroy(User $user, UserContact $contact)
    {
        $contact->delete();

        return $this->deletedResponse();
    }
}
