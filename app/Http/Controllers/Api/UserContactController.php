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
        $data = QueryBuilder::for(UserContact::tenanted()->where('user_id', $user->id))
            ->allowedFilters([
                'type', 'name', 'id_number', 'email', 'phone',
            ])
            ->allowedSorts([
                'id', 'type', 'name', 'id_number', 'email', 'phone', 'created_at',
            ])
            ->paginate($this->per_page);

        return UserContactResource::collection($data);
    }

    public function show(User $user, int $id)
    {
        $contact = UserContact::findTenanted($id);
        return new UserContactResource($contact);
    }

    public function store(User $user, StoreRequest $request)
    {
        $contact = $user->contacts()->create($request->validated());

        return new UserContactResource($contact);
    }

    public function update(User $user, StoreRequest $request, int $id)
    {
        $contact = UserContact::findTenanted($id);
        $contact->update($request->validated());

        return new UserContactResource($contact);
    }

    public function destroy(User $user, int $id)
    {
        $contact = UserContact::findTenanted($id);
        $contact->delete();

        return $this->deletedResponse();
    }
}
