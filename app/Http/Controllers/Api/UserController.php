<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\User\StoreRequest;
use App\Http\Requests\Api\User\UpdateRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:user_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:user_access', ['only' => ['restore']]);
        $this->middleware('permission:user_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:user_create', ['only' => 'store']);
        $this->middleware('permission:user_edit', ['only' => 'update']);
        $this->middleware('permission:user_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $users = QueryBuilder::for(User::tenanted()->with(['roles' => fn ($q) => $q->select('id', 'name')]))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('manager_id'),
                'name', 'email', 'type', 'nik', 'phone', 'marital_status'
            ])
            ->allowedIncludes(['detail', 'payrollInfo', 'experiences', 'educations', 'contacts'])
            ->allowedSorts([
                'id', 'branch_id', 'manager_id', 'name', 'email', 'type', 'nik', 'phone', 'marital_status', 'created_at'
            ])
            ->paginate($this->per_page);

        return UserResource::collection($users);
    }

    public function me()
    {
        /** @var User $user */
        $user = auth()->user();
        // dump($user->getAllPermissions()->pluck('name'));
        // dd($user);
        // return new UserResource($user);
        return new UserResource($user->load(['roles' => fn ($q) => $q->select('id', 'name')]));
    }

    public function show(User $user)
    {
        // abort_if(!auth()->user()->tokenCan('user_access'), 403);
        return new UserResource($user->load(['roles' => fn ($q) => $q->select('id', 'name')]));
    }

    public function store(StoreRequest $request)
    {
        dd($request->validated());
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password ?? null,
                'phone' => $request->phone,
                'address' => $request->address,
                'tax_address' => $request->tax_address,
                'provider_id' => $request->provider_id,
                'provider_name' => $request->provider_name,
                'city' => $request->city,
                'province' => $request->province,
                'zip_code' => $request->zip_code,
                'country' => $request->country,
                'contact_person' => $request->contact_person,
                'web_page' => $request->web_page,
                'type' => $request->type,
            ]);
            $user->syncRoles($request->role_ids);
            return $user;
        });

        return new UserResource($user);
    }

    public function update(User $user, UpdateRequest $request)
    {
        $data = $request->validated();

        if ($request->password) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        $user->syncRoles($request->role_ids);
        return (new UserResource($user))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(User $user)
    {
        if ($user->id == 1) return response()->json(['message' => 'Admin dengan id 1 tidak dapat dihapus!']);
        // abort_if(!auth()->user()->tokenCan('user_delete'), 403);
        $user->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        if ($id == 1) return response()->json(['message' => 'Admin dengan id 1 tidak dapat dihapus!']);
        // abort_if(!auth()->user()->tokenCan('user_delete'), 403);
        $user = User::withTrashed()->findOrFail($id);
        $user->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        // abort_if(!auth()->user()->tokenCan('user_access'), 403);
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return new UserResource($user);
    }
}
