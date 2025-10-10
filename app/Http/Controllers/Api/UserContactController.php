<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserContact\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\User;
use App\Models\UserContact;
use Spatie\QueryBuilder\QueryBuilder;

class UserContactController extends BaseController
{
    public function index(int $id)
    {
        $data = QueryBuilder::for(UserContact::where('user_id', $id))
            ->allowedFilters([
                'type',
                'name',
                'id_number',
                'email',
                'phone',
            ])
            ->allowedSorts([
                'id',
                'type',
                'name',
                'id_number',
                'email',
                'phone',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id, int $contactId)
    {
        $contact = UserContact::findOrFail($contactId);
        return new DefaultResource($contact);
    }

    public function store(User $user, StoreRequest $request)
    {
        $contact = $user->contacts()->create($request->validated());

        return new DefaultResource($contact);
    }

    public function update(int $id, StoreRequest $request, int $contactId)
    {
        $contact = UserContact::findOrFail($contactId);
        $contact->update($request->validated());

        return new DefaultResource($contact);
    }

    public function destroy(int $id, int $contactId)
    {
        $contact = UserContact::findOrFail($contactId);
        $contact->delete();

        return $this->deletedResponse();
    }
}
