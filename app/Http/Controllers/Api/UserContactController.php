<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserContact\StoreRequest;
use App\Http\Resources\UserContact\UserContactResource;
use App\Models\User;
use App\Models\UserContact;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserContactController extends BaseController
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
        $data = QueryBuilder::for(UserContact::where('user_id', $user->id))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                'type', 'name', 'id_number', 'email', 'phone'
            ])
            ->allowedSorts([
                'id', 'user_id', 'type', 'name', 'id_number', 'email', 'phone', 'created_at'
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
